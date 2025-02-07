<?php

declare(strict_types=1);

namespace App\Tests\Integration\Api\Admin;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\DecisionType;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\DocumentFileSet;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\DocumentFileUpload;
use App\Domain\Publication\Dossier\Type\WooDecision\Repository\DocumentFileSetRepository;
use App\Service\Uploader\UploadGroupId;
use App\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Integration\IntegrationTestTrait;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

final class WooDecisionUploadTest extends ApiTestCase
{
    use IntegrationTestTrait;

    private vfsStreamDirectory $root;

    private DocumentFileSetRepository $documentFileSetRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = vfsStream::setup();

        self::bootKernel();

        $this->documentFileSetRepository = static::getContainer()->get(DocumentFileSetRepository::class);
    }

    public function testUploadingSingleFile(): void
    {
        $user = UserFactory::new()
            ->asDossierAdmin()
            ->isEnabled()
            ->create()
            ->_real();

        $wooDecision = WooDecisionFactory::createOne([
            'decision' => DecisionType::PUBLIC,
            'status' => DossierStatus::CONCEPT,
            'organisation' => $user->getOrganisation(),
        ])->_real();

        $client = static::createClient()->loginUser($user, 'balie');

        vfsStream::newFile($fileName = 'test_file.pdf')
            ->withContent('This is a test file.')
            ->at($this->root);

        $uploadFile = new UploadedFile(
            path: sprintf('%s/%s', $this->root->url(), $fileName),
            originalName: $fileName,
        );

        $this->uploadFile($client, $wooDecision->getId(), $uploadFile);
        self::assertResponseIsSuccessful();

        /** @var DocumentFileSet $documentFileSet */
        $documentFileSet = $this->documentFileSetRepository->findUncompletedByDossier($wooDecision);

        self::assertNotNull($documentFileSet);
        self::assertCount(1, $documentFileSet->getUploads());

        /** @var DocumentFileUpload $documentFileUpload */
        $documentFileUpload = $documentFileSet->getUploads()->first();

        self::assertSame($fileName, $documentFileUpload->getFileInfo()->getName());
    }

    public function testUploadingMultipleFiles(): void
    {
        $user = UserFactory::new()
            ->asDossierAdmin()
            ->isEnabled()
            ->create()
            ->_real();

        $wooDecision = WooDecisionFactory::createOne([
            'decision' => DecisionType::PUBLIC,
            'status' => DossierStatus::CONCEPT,
            'organisation' => $user->getOrganisation(),
        ])->_real();

        $client = static::createClient()->loginUser($user, 'balie');

        vfsStream::newFile($fileNameOne = 'test_file_one.pdf')
            ->withContent('This is a test file.')
            ->at($this->root);

        vfsStream::newFile($fileNameTwo = 'test_file_two.pdf')
            ->withContent('This is a test file.')
            ->at($this->root);

        $uploadFileOne = new UploadedFile(
            path: sprintf('%s/%s', $this->root->url(), $fileNameOne),
            originalName: $fileNameOne,
        );

        $uploadFileTwo = new UploadedFile(
            path: sprintf('%s/%s', $this->root->url(), $fileNameTwo),
            originalName: $fileNameTwo,
        );

        $this->uploadFile($client, $wooDecision->getId(), $uploadFileOne);
        self::assertResponseIsSuccessful();

        $this->uploadFile($client, $wooDecision->getId(), $uploadFileTwo);
        self::assertResponseIsSuccessful();

        $documentFileSet = $this->documentFileSetRepository->findUncompletedByDossier($wooDecision);

        /** @var DocumentFileSet $documentFileSet */
        self::assertNotNull($documentFileSet);

        self::assertCount(2, $documentFileSet->getUploads());

        self::assertSame($fileNameOne, $documentFileSet->getUploads()[0]?->getFileInfo()->getName());
        self::assertSame($fileNameTwo, $documentFileSet->getUploads()[1]?->getFileInfo()->getName());
    }

    public function testUploadingToNonExistingWooDecision(): void
    {
        $user = UserFactory::new()
            ->asDossierAdmin()
            ->isEnabled()
            ->create()
            ->_real();

        $client = static::createClient()->loginUser($user, 'balie');

        vfsStream::newFile($fileName = 'test_file.pdf')
            ->withContent('This is a test file.')
            ->at($this->root);

        $uploadFile = new UploadedFile(
            path: sprintf('%s/%s', $this->root->url(), $fileName),
            originalName: $fileName,
        );

        $this->uploadFile($client, Uuid::v6(), $uploadFile);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUploadingToDossierWithoutAccess(): void
    {
        $owner = UserFactory::new()
            ->asDossierAdmin()
            ->isEnabled()
            ->create()
            ->_real();

        $user = UserFactory::new()
            ->asDossierAdmin()
            ->isEnabled()
            ->create()
            ->_real();

        $wooDecision = WooDecisionFactory::createOne([
            'decision' => DecisionType::PUBLIC,
            'status' => DossierStatus::CONCEPT,
            'organisation' => $owner->getOrganisation(),
        ])->_real();

        $client = static::createClient()->loginUser($user, 'balie');

        vfsStream::newFile($fileName = 'test_file.pdf')
            ->withContent('This is a test file.')
            ->at($this->root);

        $uploadFile = new UploadedFile(
            path: sprintf('%s/%s', $this->root->url(), $fileName),
            originalName: $fileName,
        );

        $this->uploadFile($client, $wooDecision->getId(), $uploadFile);
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $documentFileSet = $this->documentFileSetRepository->findUncompletedByDossier($wooDecision);

        self::assertNull($documentFileSet);
    }

    private function uploadFile(Client $client, Uuid $wooDecisionId, UploadedFile $file): void
    {
        $client->request(
            Request::METHOD_POST,
            sprintf('/balie/uploader/woo-decision/%s', $wooDecisionId),
            [
                'headers' => [
                    'Content-Type' => 'multipart/form-data',
                    'Accept' => 'application/json',
                ],
                'extra' => [
                    'parameters' => [
                        'chunkindex' => '0',
                        'totalchunkcount' => '1',
                        'groupId' => UploadGroupId::WOO_DECISION_DOCUMENTS->value,
                        'uuid' => 'file-' . $this->getFaker()->uuid(),
                    ],
                    'files' => [
                        'file' => $file,
                    ],
                ],
            ],
        );
    }
}
