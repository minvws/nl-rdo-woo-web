<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Dossier\Uploads\Document;

use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PublicationApi\Api\Dossier\WooDecision\Uploads\Document\DocumentFileName;
use PublicationApi\Tests\Integration\Api\ApiPublicationV1TestCase;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawReason;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Domain\Upload\StreamUpload;
use Shared\Domain\Upload\UploadService;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\DocumentFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionMainDocumentFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\UuidV6;

use function file_get_contents;
use function sprintf;
use function str_repeat;
use function Zenstruck\Foundry\Persistence\save;

final class WooDecisionUploadDocumentTest extends ApiPublicationV1TestCase
{
    public function testUploadWooDecisionDocument(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::createOne([
            'organisation' => $organisation,
            'externalId' => $this->getFaker()->externalId(),
            'previewDate' => $this->getFaker()->plainDate(),
            'departments' => [$department],
        ]);
        WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecision]);
        $document = DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'externalId' => $this->getFaker()->externalId(),
            'judgement' => Judgement::PUBLIC,
        ]);

        $client = self::createPublicationApiClient();
        $fileContent = $this->getTestFileContent('1008.pdf');

        $uploadService = Mockery::mock(UploadService::class);
        self::getContainer()->set(UploadService::class, $uploadService);

        $uploadService
            ->expects('handleUpload')
            ->with(
                Mockery::on(static function (StreamUpload $streamUpload) use ($document, $wooDecision, $fileContent): bool {
                    if ($streamUpload->fileName !== new DocumentFileName($document)->fileName) {
                        return false;
                    }

                    if ($streamUpload->stream->getContents() !== $fileContent) {
                        return false;
                    }

                    if ($streamUpload->groupId !== UploadGroupId::API_WOO_DECISION_DOCUMENTS) {
                        return false;
                    }

                    if ($streamUpload->additionalParameters->get('dossierId') !== $wooDecision->getId()->toRfc4122()) {
                        return false;
                    }

                    if ($streamUpload->additionalParameters->get('documentId') !== $document->getId()->toRfc4122()) {
                        return false;
                    }

                    if (! UuidV6::isValid($streamUpload->uploadId)) {
                        return false;
                    }

                    return true;
                }),
            );

        $url = sprintf(
            '/api/publication/v1/organisation/%s/dossiers/woo-decision/external/%s/uploads/document/external/%s',
            $organisation->getId(),
            $wooDecision->getExternalId(),
            $document->getExternalId()?->toString(),
        );

        $client->request(Request::METHOD_PUT, $url, [
            'headers' => [
                'Content-Type' => 'application/octet-stream',
            ],
            'body' => $fileContent,
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testUploadWooDecisionDocumentWithoutFile(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::createOne([
            'organisation' => $organisation,
            'externalId' => $this->getFaker()->externalId(),
            'previewDate' => $this->getFaker()->plainDate(),
            'departments' => [$department],
        ]);
        $document = DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'externalId' => $this->getFaker()->externalId(),
        ]);
        $client = self::createPublicationApiClient();

        $url = sprintf(
            '/api/publication/v1/organisation/%s/dossiers/woo-decision/external/%s/uploads/document/external/%s',
            $organisation->getId(),
            $wooDecision->getExternalId(),
            $document->getExternalId()?->toString(),
        );
        $client->request(Request::METHOD_PUT, $url, [
            'headers' => [
                'Content-Type' => 'application/octet-stream',
            ],
            'body' => '',
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testUploadWooDecisionDocumentWhenDocumentIsSuspended(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::createOne([
            'organisation' => $organisation,
            'externalId' => $this->getFaker()->externalId(),
            'previewDate' => $this->getFaker()->plainDate(),
            'departments' => [$department],
        ]);
        $document = DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'externalId' => $this->getFaker()->externalId(),
            'suspended' => true,
        ]);

        $client = self::createPublicationApiClient();

        $testFileName = '1008.pdf';
        $testFilePath = sprintf('%s/tests/robot_framework/files/woodecision/%s', static::$kernel?->getProjectDir(), $testFileName);
        $fileContent = file_get_contents($testFilePath);

        $uploadService = Mockery::mock(UploadService::class);
        self::getContainer()->set(UploadService::class, $uploadService);

        $url = sprintf(
            '/api/publication/v1/organisation/%s/dossiers/woo-decision/external/%s/uploads/document/external/%s',
            $organisation->getId(),
            $wooDecision->getExternalId(),
            $document->getExternalId()?->toString(),
        );

        $client->request(Request::METHOD_PUT, $url, [
            'headers' => [
                'Content-Type' => 'application/octet-stream',
            ],
            'body' => $fileContent,
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testUploadWooDecisionDocumentWhenDocumentIsWithdrawn(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::createOne([
            'organisation' => $organisation,
            'externalId' => $this->getFaker()->externalId(),
            'previewDate' => $this->getFaker()->plainDate(),
            'departments' => [$department],
        ]);
        $document = DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'externalId' => $this->getFaker()->externalId(),
        ]);
        $document->withdraw(DocumentWithdrawReason::SUSPENDED_DOCUMENT, 'explanation');
        save($document);

        $client = self::createPublicationApiClient();

        $testFileName = '1008.pdf';
        $testFilePath = sprintf('%s/tests/robot_framework/files/woodecision/%s', static::$kernel?->getProjectDir(), $testFileName);
        $fileContent = file_get_contents($testFilePath);

        $uploadService = Mockery::mock(UploadService::class);
        self::getContainer()->set(UploadService::class, $uploadService);

        $url = sprintf(
            '/api/publication/v1/organisation/%s/dossiers/woo-decision/external/%s/uploads/document/external/%s',
            $organisation->getId(),
            $wooDecision->getExternalId(),
            $document->getExternalId()?->toString(),
        );

        $client->request(Request::METHOD_PUT, $url, [
            'headers' => [
                'Content-Type' => 'application/octet-stream',
            ],
            'body' => $fileContent,
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    #[DataProvider('uploadWooDecisionDocumentForAllJudgementsDataProvider')]
    public function testUploadWooDecisionDocumentForAllJudgements(
        Judgement $judgement,
        int $expectedResponseCode,
    ): void {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::createOne([
            'organisation' => $organisation,
            'externalId' => $this->getFaker()->externalId(),
            'previewDate' => $this->getFaker()->plainDate(),
            'departments' => [$department],
        ]);
        $document = DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'externalId' => $this->getFaker()->externalId(),
            'judgement' => $judgement,
        ]);

        $client = $this->createPublicationApiClient();

        $testFileName = '1008.pdf';
        $testFilePath = sprintf('%s/tests/robot_framework/files/woodecision/%s', static::$kernel?->getProjectDir(), $testFileName);
        $fileContent = file_get_contents($testFilePath);

        $uploadService = Mockery::mock(UploadService::class);
        if ($expectedResponseCode === Response::HTTP_NO_CONTENT) {
            $uploadService->expects('handleUpload');
        }

        self::getContainer()->set(UploadService::class, $uploadService);

        $url = sprintf(
            '/api/publication/v1/organisation/%s/dossiers/woo-decision/external/%s/uploads/document/external/%s',
            $organisation->getId(),
            $wooDecision->getExternalId(),
            $document->getExternalId()?->toString(),
        );

        $client->request(Request::METHOD_PUT, $url, [
            'headers' => [
                'Content-Type' => 'application/octet-stream',
            ],
            'body' => $fileContent,
        ]);

        self::assertResponseStatusCodeSame($expectedResponseCode);
    }

    /**
     * @return array<string,array<array-key,mixed>>
     */
    public static function uploadWooDecisionDocumentForAllJudgementsDataProvider(): array
    {
        return [
            Judgement::PUBLIC->value => [
                'judgement' => Judgement::PUBLIC,
                'expectedResponseCode' => Response::HTTP_NO_CONTENT,
            ],
            Judgement::ALREADY_PUBLIC->value => [
                'judgement' => Judgement::ALREADY_PUBLIC,
                'expectedResponseCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
            ],
            Judgement::PARTIAL_PUBLIC->value => [
                'judgement' => Judgement::PARTIAL_PUBLIC,
                'expectedResponseCode' => Response::HTTP_NO_CONTENT,
            ],
            Judgement::NOT_PUBLIC->value => [
                'judgement' => Judgement::NOT_PUBLIC,
                'expectedResponseCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
            ],
        ];
    }

    public function testUploadWooDecisionDocumentWithTooLongDossierExternalId(): void
    {
        $organisation = OrganisationFactory::createOne();

        $client = self::createPublicationApiClient();
        $client->request(Request::METHOD_PUT, sprintf(
            '/api/publication/v1/organisation/%s/dossiers/woo-decision/external/%s/uploads/document/external/%s',
            $organisation->getId(),
            str_repeat('x', 129),
            'some-id',
        ));

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testUploadWooDecisionDocumentWithTooLongDocumentExternalId(): void
    {
        $organisation = OrganisationFactory::createOne();

        $client = self::createPublicationApiClient();
        $client->request(Request::METHOD_PUT, sprintf(
            '/api/publication/v1/organisation/%s/dossiers/woo-decision/external/%s/uploads/document/external/%s',
            $organisation->getId(),
            'some-id',
            str_repeat('x', 129),
        ));

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
