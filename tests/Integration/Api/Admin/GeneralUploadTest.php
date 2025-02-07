<?php

declare(strict_types=1);

namespace App\Tests\Integration\Api\Admin;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Service\Uploader\UploadGroupId;
use App\Tests\Factory\UserFactory;
use App\Tests\Integration\IntegrationTestTrait;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class GeneralUploadTest extends ApiTestCase
{
    use IntegrationTestTrait;

    private vfsStreamDirectory $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = vfsStream::setup();

        self::bootKernel();
    }

    public function testUploading(): void
    {
        $user = UserFactory::new()
            ->asDossierAdmin()
            ->isEnabled()
            ->create()
            ->_real();

        vfsStream::newFile('test_file.txt')
            ->withContent('This is a test file.')
            ->at($this->root);

        $uploadFile = new UploadedFile(
            path: $this->root->url() . '/test_file.txt',
            originalName: 'test_file.txt',
        );

        $client = static::createClient()->loginUser($user, 'balie');

        $this->uploadFile($client, $uploadFile, UploadGroupId::ATTACHMENTS->value);

        self::assertResponseIsSuccessful();
        self::assertMatchesJsonSnapshot($client->getResponse()?->toArray(false));
    }

    public function testUploadingWithoutAuthorisation(): void
    {
        $user = UserFactory::new()
            ->asViewAccess()
            ->isEnabled()
            ->create()
            ->_real();

        vfsStream::newFile('test_file.txt')
            ->withContent('This is a test file.')
            ->at($this->root);

        $uploadFile = new UploadedFile(
            path: $this->root->url() . '/test_file.txt',
            originalName: 'test_file.txt',
        );

        $client = static::createClient()->loginUser($user, 'balie');

        $this->uploadFile($client, $uploadFile, UploadGroupId::ATTACHMENTS->value);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testUploadingWithInvalidGroupId(): void
    {
        $user = UserFactory::new()
            ->asDossierAdmin()
            ->isEnabled()
            ->create()
            ->_real();

        vfsStream::newFile('test_file.txt')
            ->withContent('This is a test file')
            ->at($this->root);

        $uploadFile = new UploadedFile(
            path: $this->root->url() . '/test_file.txt',
            originalName: 'test_file.txt',
        );

        $client = static::createClient()->loginUser($user, 'balie');
        $this->uploadFile($client, $uploadFile, 'INVALID');

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        self::assertMatchesJsonSnapshot($client->getResponse()?->toArray(false));
    }

    private function uploadFile(Client $client, UploadedFile $file, string $groupdId): void
    {
        $client->request(
            Request::METHOD_POST,
            '/balie/uploader',
            [
                'headers' => [
                    'Content-Type' => 'multipart/form-data',
                    'Accept' => 'application/json',
                ],
                'extra' => [
                    'parameters' => [
                        'chunkindex' => '0',
                        'totalchunkcount' => '1',
                        'groupId' => $groupdId,
                        'uuid' => 'f031b587-1b85-3183-8c94-7d524c68c37b',
                    ],
                    'files' => [
                        'file' => $file,
                    ],
                ],
            ],
        );
    }
}
