<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Publication\Dossier\Uploads\MainDocument;

use Mockery;
use PublicationApi\Tests\Integration\Api\Publication\ApiPublicationV1TestCase;
use Shared\Domain\Upload\Handler\UploadHandlerInterface;
use Shared\Domain\Upload\UploadRequest;
use Shared\Domain\Upload\UploadService;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionMainDocumentFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function file_get_contents;
use function sprintf;

final class WooDecisionUploadMainDocumentTest extends ApiPublicationV1TestCase
{
    public function testUploadWooDecisionMainDocument(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::createOne([
            'organisation' => $organisation,
            'previewDate' => $this->getFaker()->dateTime(),
            'departments' => [$department],
        ]);
        $wooDecisionMainDocument = WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecision]);
        $client = self::createPublicationApiClient();

        $testFileName = '1008.pdf';
        $testFilePath = sprintf('%s/tests/robot_framework/files/woodecision/%s', static::$kernel->getProjectDir(), $testFileName);
        $fileContent = file_get_contents($testFilePath);

        $uploadService = Mockery::mock(UploadService::class);
        self::getContainer()->set(UploadService::class, $uploadService);
        $uploadService
            ->shouldReceive('handleUploadRequest')
            ->once()
            ->with(
                Mockery::on(function (UploadRequest $uploadRequest) use ($wooDecisionMainDocument) {
                    if ($uploadRequest->chunkIndex !== 1) {
                        return false;
                    }

                    if ($uploadRequest->chunkCount !== 1) {
                        return false;
                    }

                    if ($uploadRequest->uploadedFile->getClientOriginalName() !== $wooDecisionMainDocument->getFileInfo()->getName()) {
                        return false;
                    }

                    if ($uploadRequest->groupId !== UploadGroupId::MAIN_DOCUMENTS) {
                        return false;
                    }

                    return true;
                }),
                null
            );
        $url = sprintf(
            '/api/publication/v1/organisation/%s/dossiers/woo-decision/%s/uploads/main-document/%s',
            $organisation->getId(),
            $wooDecision->getId(),
            $wooDecisionMainDocument->getId(),
        );

        $client->request(Request::METHOD_PUT, $url, [
            'headers' => [
                'Content-Type' => 'application/octet-stream',
            ],
            'body' => $fileContent,
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testUploadWooDecisionMainDocumentWithoutFile(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::createOne([
            'organisation' => $organisation,
            'previewDate' => $this->getFaker()->dateTime(),
            'departments' => [$department],
        ]);
        $wooDecisionMainDocument = WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecision]);
        $client = self::createPublicationApiClient();

        $uploadHandler = Mockery::mock(UploadHandlerInterface::class);
        self::getContainer()->set(UploadHandlerInterface::class, $uploadHandler);
        $uploadHandler
            ->shouldReceive('handleUpload')
            ->never();

        $url = sprintf(
            '/api/publication/v1/organisation/%s/dossiers/woo-decision/%s/uploads/main-document/%s',
            $organisation->getId(),
            $wooDecision->getId(),
            $wooDecisionMainDocument->getId(),
        );
        $client->request(Request::METHOD_PUT, $url, [
            'headers' => [
                'Content-Type' => 'application/octet-stream',
            ],
            'body' => '',
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
