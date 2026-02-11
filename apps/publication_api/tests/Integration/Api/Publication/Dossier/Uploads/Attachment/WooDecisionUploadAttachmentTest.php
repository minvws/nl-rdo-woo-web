<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Publication\Dossier\Uploads\Attachment;

use Mockery;
use PublicationApi\Tests\Integration\Api\Publication\ApiPublicationV1TestCase;
use Shared\Domain\Upload\Handler\UploadHandlerInterface;
use Shared\Domain\Upload\UploadRequest;
use Shared\Domain\Upload\UploadService;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\ValueObject\ExternalId;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function file_get_contents;
use function sprintf;

final class WooDecisionUploadAttachmentTest extends ApiPublicationV1TestCase
{
    public function testUploadWooDecisionAttachment(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::createOne([
            'organisation' => $organisation,
            'externalId' => ExternalId::create($this->getFaker()->uuid()),
            'previewDate' => $this->getFaker()->dateTime(),
            'departments' => [$department],
        ]);
        $wooDecisionAttachment = WooDecisionAttachmentFactory::createOne([
            'dossier' => $wooDecision,
            'externalId' => ExternalId::create($this->getFaker()->uuid()),
        ]);
        $client = self::createPublicationApiClient();

        $testFileName = '1008.pdf';
        $testFilePath = sprintf('%s/tests/robot_framework/files/woodecision/%s', static::$kernel->getProjectDir(), $testFileName);
        $fileContent = file_get_contents($testFilePath);

        $uploadService = Mockery::mock(UploadService::class);
        self::getContainer()->set(UploadService::class, $uploadService);
        $uploadService->expects('handleUploadRequest')
            ->with(
                Mockery::on(function (UploadRequest $uploadRequest) use ($wooDecisionAttachment) {
                    if ($uploadRequest->chunkIndex !== 1) {
                        return false;
                    }

                    if ($uploadRequest->chunkCount !== 1) {
                        return false;
                    }

                    if ($uploadRequest->uploadedFile->getClientOriginalName() !== $wooDecisionAttachment->getFileInfo()->getName()) {
                        return false;
                    }

                    if ($uploadRequest->groupId !== UploadGroupId::ATTACHMENTS) {
                        return false;
                    }

                    return true;
                }),
                null
            );
        $url = sprintf(
            '/api/publication/v1/organisation/%s/dossiers/woo-decision/E:%s/uploads/attachment/E:%s',
            $organisation->getId(),
            $wooDecision->getExternalId(),
            $wooDecisionAttachment->getExternalId(),
        );

        $client->request(Request::METHOD_PUT, $url, [
            'headers' => [
                'Content-Type' => 'application/octet-stream',
            ],
            'body' => $fileContent,
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testUploadWooDecisionAttachmentWithoutFile(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::createOne([
            'organisation' => $organisation,
            'externalId' => ExternalId::create($this->getFaker()->uuid()),
            'previewDate' => $this->getFaker()->dateTime(),
            'departments' => [$department],
        ]);
        $wooDecisionAttachment = WooDecisionAttachmentFactory::createOne([
            'dossier' => $wooDecision,
            'externalId' => ExternalId::create($this->getFaker()->uuid()),
        ]);
        $client = self::createPublicationApiClient();

        $uploadHandler = Mockery::mock(UploadHandlerInterface::class);
        self::getContainer()->set(UploadHandlerInterface::class, $uploadHandler);
        $uploadHandler
            ->shouldReceive('handleUpload')
            ->never();

        $url = sprintf(
            '/api/publication/v1/organisation/%s/dossiers/woo-decision/E:%s/uploads/attachment/E:%s',
            $organisation->getId(),
            $wooDecision->getExternalId(),
            $wooDecisionAttachment->getExternalId(),
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
