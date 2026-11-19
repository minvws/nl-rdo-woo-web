<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Dossier\Uploads\MainDocument;

use GuzzleHttp\Psr7\Utils;
use Mockery;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PublicationApi\Tests\Integration\Api\Dossier\Uploads\ApiPublicationV1UploadTestCase;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\MainDocument\SpreadsheetTable\SpreadsheetTableException;
use Shared\Domain\Upload\AntiVirus\ClamAvFileScanner;
use Shared\Domain\Upload\AntiVirus\FileScanResult;
use Shared\Domain\Upload\Handler\S3\S3UploadHelper;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\FileInfoFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Disposition\DispositionFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Disposition\DispositionMainDocumentFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webmozart\Assert\Assert;

use function file_get_contents;
use function sprintf;
use function str_repeat;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

final class DispositionUploadMainDocumentTest extends ApiPublicationV1UploadTestCase
{
    public function testUpload(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $dispostion = DispositionFactory::new()->concept()->create([
            'organisation' => $organisation,
            'externalId' => $this->getFaker()->externalId(),
            'departments' => [$department],
        ]);
        $dispositionMainDocument = DispositionMainDocumentFactory::createOne([
            'dossier' => $dispostion,
        ]);

        $this->assertUpload(
            url: sprintf(
                '/api/publication/v1/organisation/%s/dossiers/disposition/external/%s/uploads/main-document',
                $organisation->getId(),
                $dispostion->getExternalId(),
            ),
            dossierId: $dispostion->getId()->toRfc4122(),
            entityId: $dispositionMainDocument->getId()->toRfc4122(),
            entityFileName: $dispositionMainDocument->getFileInfo()->getName(),
            uploadGroupId: UploadGroupId::MAIN_DOCUMENTS,
            entityParameterKey: 'mainDocumentId',
        );
    }

    public function testUploadOnPublishedDossier(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $disposition = DispositionFactory::createOne([
            'organisation' => $organisation,
            'externalId' => $this->getFaker()->externalId(),
            'departments' => [$department],
            'status' => DossierStatus::PUBLISHED,
        ]);
        $dispositionMainDocument = DispositionMainDocumentFactory::createOne([
            'dossier' => $disposition,
        ]);

        $this->assertUpload(
            url: sprintf(
                '/api/publication/v1/organisation/%s/dossiers/disposition/external/%s/uploads/main-document',
                $organisation->getId(),
                $disposition->getExternalId(),
            ),
            dossierId: $disposition->getId()->toRfc4122(),
            entityId: $dispositionMainDocument->getId()->toRfc4122(),
            entityFileName: $dispositionMainDocument->getFileInfo()->getName(),
            uploadGroupId: UploadGroupId::MAIN_DOCUMENTS,
            entityParameterKey: 'mainDocumentId',
        );
    }

    public function testUploadWithoutFile(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $disposition = DispositionFactory::createOne([
            'organisation' => $organisation,
            'externalId' => $this->getFaker()->externalId(),
            'departments' => [$department],
        ]);

        $this->assertUploadWithoutFile(sprintf(
            '/api/publication/v1/organisation/%s/dossiers/disposition/external/%s/uploads/main-document',
            $organisation->getId(),
            $disposition->getExternalId(),
        ));
    }

    public function testUploadWithTooLongDossierExternalId(): void
    {
        $organisation = OrganisationFactory::createOne();

        $client = self::createPublicationApiClient();
        $client->request(Request::METHOD_PUT, sprintf(
            '/api/publication/v1/organisation/%s/dossiers/disposition/external/%s/uploads/main-document',
            $organisation->getId(),
            str_repeat('x', 129),
        ));

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testUploadWithMultipleWorksheetsReturns422WithReason(): void
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            ['Header'],
            ['Value'],
        ]);
        $spreadsheet->createSheet();

        $this->assertInvalidSpreadsheetUploadReturns422(
            $spreadsheet,
            SpreadsheetTableException::forMultipleWorksheets(),
        );
    }

    public function testUploadWithoutHeaderRowReturns422WithReason(): void
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            [null],
            ['Value'],
        ]);

        $this->assertInvalidSpreadsheetUploadReturns422(
            $spreadsheet,
            SpreadsheetTableException::forMissingHeaderRow(),
        );
    }

    private function assertInvalidSpreadsheetUploadReturns422(
        Spreadsheet $spreadsheet,
        SpreadsheetTableException $exception,
    ): void {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $disposition = DispositionFactory::new()->concept()->create([
            'organisation' => $organisation,
            'externalId' => $this->getFaker()->externalId(),
            'departments' => [$department],
        ]);
        $fileName = 'existing.xlsx';
        DispositionMainDocumentFactory::createOne([
            'dossier' => $disposition,
            'fileInfo' => FileInfoFactory::createOne([
                'name' => $fileName,
                'mimetype' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'type' => 'xlsx',
                'size' => 123,
                'path' => sprintf('existing/%s', $fileName),
            ]),
        ]);
        $content = $this->createSpreadsheetContent($spreadsheet);

        $client = self::createPublicationApiClient();
        $clamAvFileScanner = Mockery::mock(ClamAvFileScanner::class);
        $clamAvFileScanner->expects('getFileSizeLimit')->twice()->andReturn(100_000_000);
        $clamAvFileScanner->expects('scanResource')->andReturn(FileScanResult::SAFE);
        self::getContainer()->set(ClamAvFileScanner::class, $clamAvFileScanner);

        $s3UploadHelper = Mockery::mock(S3UploadHelper::class);
        $s3UploadHelper->expects('uploadStream');
        $s3UploadHelper->expects('readStream')->andReturn(Utils::streamFor($content));
        $s3UploadHelper->expects('deleteUpload');
        self::getContainer()->set(S3UploadHelper::class, $s3UploadHelper);

        $client->request(
            Request::METHOD_PUT,
            sprintf(
                '/api/publication/v1/organisation/%s/dossiers/disposition/external/%s/uploads/main-document',
                $organisation->getId(),
                $disposition->getExternalId(),
            ),
            [
                'headers' => ['Content-Type' => 'application/octet-stream'],
                'body' => $content,
            ],
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains([
            'violations' => [
                ['message' => $exception->getMessage()],
            ],
        ]);
    }

    private function createSpreadsheetContent(Spreadsheet $spreadsheet): string
    {
        $path = tempnam(sys_get_temp_dir(), 'publication-api-upload-');
        Assert::string($path);

        try {
            new Xlsx($spreadsheet)->save($path);

            $content = file_get_contents($path);
            Assert::string($content);

            return $content;
        } finally {
            $spreadsheet->disconnectWorksheets();
            unlink($path);
        }
    }
}
