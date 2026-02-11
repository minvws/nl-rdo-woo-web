<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Publication\Dossier\Uploads\Document;

use Mockery;
use PublicationApi\Tests\Integration\Api\Publication\ApiPublicationV1TestCase;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawReason;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Domain\Upload\Handler\UploadHandlerInterface;
use Shared\Domain\Upload\UploadRequest;
use Shared\Domain\Upload\UploadService;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\DocumentFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\ValueObject\ExternalId;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function file_get_contents;
use function sprintf;
use function Zenstruck\Foundry\Persistence\save;

final class WooDecisionUploadDocumentTest extends ApiPublicationV1TestCase
{
    public function testUploadWooDecisionDocument(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::createOne([
            'organisation' => $organisation,
            'externalId' => $this->getFaker()->uuid(),
            'previewDate' => $this->getFaker()->dateTime(),
            'departments' => [$department],
        ]);
        $document = DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'externalId' => ExternalId::create($this->getFaker()->uuid()),
            'judgement' => Judgement::PUBLIC,
        ]);

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
                Mockery::on(function (UploadRequest $uploadRequest) use ($document) {
                    if ($uploadRequest->chunkIndex !== 1) {
                        return false;
                    }

                    if ($uploadRequest->chunkCount !== 1) {
                        return false;
                    }

                    if ($uploadRequest->uploadedFile->getClientOriginalName() !== $document->getFileInfo()->getName()) {
                        return false;
                    }

                    if ($uploadRequest->groupId !== UploadGroupId::WOO_DECISION_DOCUMENTS) {
                        return false;
                    }

                    return true;
                }),
                null
            );
        $url = sprintf(
            '/api/publication/v1/organisation/%s/dossiers/woo-decision/E:%s/uploads/document/E:%s',
            $organisation->getId(),
            $wooDecision->getExternalId(),
            $document->getExternalId()?->__toString(),
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
            'externalId' => $this->getFaker()->uuid(),
            'previewDate' => $this->getFaker()->dateTime(),
            'departments' => [$department],
        ]);
        $document = DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'externalId' => ExternalId::create($this->getFaker()->uuid()),
        ]);
        $client = self::createPublicationApiClient();

        Mockery::mock(UploadHandlerInterface::class);

        $url = sprintf(
            '/api/publication/v1/organisation/%s/dossiers/woo-decision/E:%s/uploads/document/E:%s',
            $organisation->getId(),
            $wooDecision->getExternalId(),
            $document->getExternalId()?->__toString(),
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
            'externalId' => $this->getFaker()->uuid(),
            'previewDate' => $this->getFaker()->dateTime(),
            'departments' => [$department],
        ]);
        $document = DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'externalId' => ExternalId::create($this->getFaker()->uuid()),
            'suspended' => true,
        ]);

        $client = self::createPublicationApiClient();

        $testFileName = '1008.pdf';
        $testFilePath = sprintf('%s/tests/robot_framework/files/woodecision/%s', static::$kernel->getProjectDir(), $testFileName);
        $fileContent = file_get_contents($testFilePath);

        $uploadService = Mockery::mock(UploadService::class);
        self::getContainer()->set(UploadService::class, $uploadService);

        $url = sprintf(
            '/api/publication/v1/organisation/%s/dossiers/woo-decision/E:%s/uploads/document/E:%s',
            $organisation->getId(),
            $wooDecision->getExternalId(),
            $document->getExternalId()?->__toString(),
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
            'externalId' => $this->getFaker()->uuid(),
            'previewDate' => $this->getFaker()->dateTime(),
            'departments' => [$department],
        ]);
        $document = DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'externalId' => ExternalId::create($this->getFaker()->uuid()),
        ]);
        $document->withdraw(DocumentWithdrawReason::SUSPENDED_DOCUMENT, 'explanation');
        save($document);

        $client = self::createPublicationApiClient();

        $testFileName = '1008.pdf';
        $testFilePath = sprintf('%s/tests/robot_framework/files/woodecision/%s', static::$kernel->getProjectDir(), $testFileName);
        $fileContent = file_get_contents($testFilePath);

        $uploadService = Mockery::mock(UploadService::class);
        self::getContainer()->set(UploadService::class, $uploadService);

        $url = sprintf(
            '/api/publication/v1/organisation/%s/dossiers/woo-decision/E:%s/uploads/document/E:%s',
            $organisation->getId(),
            $wooDecision->getExternalId(),
            $document->getExternalId()?->__toString(),
        );

        $client->request(Request::METHOD_PUT, $url, [
            'headers' => [
                'Content-Type' => 'application/octet-stream',
            ],
            'body' => $fileContent,
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testUploadWooDecisionDocumentWhenDocumentIsNotPublic(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::createOne([
            'organisation' => $organisation,
            'externalId' => $this->getFaker()->uuid(),
            'previewDate' => $this->getFaker()->dateTime(),
            'departments' => [$department],
        ]);
        $document = DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'externalId' => ExternalId::create($this->getFaker()->uuid()),
            'judgement' => Judgement::NOT_PUBLIC,
        ]);

        $client = self::createPublicationApiClient();

        $testFileName = '1008.pdf';
        $testFilePath = sprintf('%s/tests/robot_framework/files/woodecision/%s', static::$kernel->getProjectDir(), $testFileName);
        $fileContent = file_get_contents($testFilePath);

        $uploadService = Mockery::mock(UploadService::class);
        self::getContainer()->set(UploadService::class, $uploadService);

        $url = sprintf(
            '/api/publication/v1/organisation/%s/dossiers/woo-decision/E:%s/uploads/document/E:%s',
            $organisation->getId(),
            $wooDecision->getExternalId(),
            $document->getExternalId()?->__toString(),
        );

        $client->request(Request::METHOD_PUT, $url, [
            'headers' => [
                'Content-Type' => 'application/octet-stream',
            ],
            'body' => $fileContent,
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
