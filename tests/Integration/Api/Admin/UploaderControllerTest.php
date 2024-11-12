<?php

declare(strict_types=1);

namespace App\Tests\Integration\Api\Admin;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Service\Uploader\UploadGroupId;
use App\Tests\Factory\UserFactory;
use App\Tests\Integration\IntegrationTestTrait;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class UploaderControllerTest extends ApiTestCase
{
    use IntegrationTestTrait;

    private vfsStreamDirectory $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = vfsStream::setup();

        self::bootKernel();
    }

    public function testUpload(): void
    {
        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create();

        vfsStream::newFile('test_file.txt')
            ->withContent(<<<'FILE'
                This is a test file.

                With another line.

                FILE
            )
            ->at($this->root);

        $uploadFile = new UploadedFile(
            path: $this->root->url() . '/test_file.txt',
            originalName: 'test_file.txt',
        );

        $response = static::createClient()
            ->loginUser($user->_real(), 'balie')
            ->request(
                'POST',
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
                            'groupId' => UploadGroupId::ATTACHMENTS->value,
                            'uuid' => 'f031b587-1b85-3183-8c94-7d524c68c37b',
                        ],
                        'files' => [
                            'file' => $uploadFile,
                        ],
                    ],
                ],
            );

        $this->assertResponseIsSuccessful();
        $this->assertMatchesJsonSnapshot($response->toArray(false));
    }

    public function testUploadWithInvalidGroupId(): void
    {
        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create();

        vfsStream::newFile('test_file.txt')
            ->withContent(<<<'FILE'
                This is a test file.

                With another line.

                FILE
            )
            ->at($this->root);

        $uploadFile = new UploadedFile(
            path: $this->root->url() . '/test_file.txt',
            originalName: 'test_file.txt',
        );

        $response = static::createClient()
            ->loginUser($user->_real(), 'balie')
            ->request(
                'POST',
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
                            'groupId' => 'INVALID',
                            'uuid' => 'f031b587-1b85-3183-8c94-7d524c68c37b',
                        ],
                        'files' => [
                            'file' => $uploadFile,
                        ],
                    ],
                ],
            );

        $this->assertResponseStatusCodeSame(400);
        $this->assertMatchesJsonSnapshot($response->toArray(false));
    }
}
