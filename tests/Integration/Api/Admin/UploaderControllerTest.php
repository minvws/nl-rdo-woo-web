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
        $this->root = vfsStream::setup();

        self::bootKernel();
    }

    public function testUpload(): void
    {
        $user = UserFactory::new()
            ->asAdmin()
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
                    'headers' => ['Content-Type' => 'multipart/form-data'],
                    'extra' => [
                        'parameters' => [
                            'chunkindex' => '0',
                            'totalchunkcount' => '1',
                            'groupId' => UploadGroupId::WOO_DECISION_ATTACHMENTS->value,
                            'uuid' => $this->getFaker()->uuid(),
                        ],
                        'files' => [
                            'file' => $uploadFile,
                        ],
                    ],
                ],
            );

        $this->assertResponseIsSuccessful();
        $this->assertJson($response->getContent());
    }
}
