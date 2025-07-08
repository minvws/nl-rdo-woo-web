<?php

declare(strict_types=1);

namespace App\Tests\Integration\Api\Admin\Uploader\Status;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Domain\Upload\UploadStatus;
use App\Tests\Factory\UploadEntityFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class UploadStatusTest extends ApiTestCase
{
    use IntegrationTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    public function testGetUploadStatusSuccessfully(): void
    {
        $user = UserFactory::new()
            ->asDossierAdmin()
            ->isEnabled()
            ->create()
            ->_real();

        $uploadId = 'foo-123';

        UploadEntityFactory::createOne([
            'uploadId' => $uploadId,
            'user' => $user,
        ]);

        $client = static::createClient()->loginUser($user, 'balie');

        $client->request(
            Request::METHOD_GET,
            sprintf('/balie/api/uploader/upload/%s/status', $uploadId),
            [
                'headers' => ['Accept' => 'application/json'],
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertJsonContains([
            'uploadId' => $uploadId,
            'status' => UploadStatus::INCOMPLETE->value,
        ]);
    }

    public function testGetUploadStatusForOtherUsersUploadIsDenied(): void
    {
        $userA = UserFactory::new()
            ->asDossierAdmin()
            ->isEnabled()
            ->create()
            ->_real();

        $userB = UserFactory::new()
            ->asDossierAdmin()
            ->isEnabled()
            ->create()
            ->_real();

        $uploadId = 'foo-123';

        UploadEntityFactory::createOne([
            'uploadId' => $uploadId,
            'user' => $userA,
        ]);

        $client = static::createClient()->loginUser($userB, 'balie');

        $client->request(
            Request::METHOD_GET,
            sprintf('/balie/api/uploader/upload/%s/status', $uploadId),
            [
                'headers' => ['Accept' => 'application/json'],
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testGetUploadStatusForUnknownUploadIsReturns404(): void
    {
        $user = UserFactory::new()
            ->asDossierAdmin()
            ->isEnabled()
            ->create()
            ->_real();

        $client = static::createClient()->loginUser($user, 'balie');

        $client->request(
            Request::METHOD_GET,
            '/balie/api/uploader/upload/non-existent-upload-id/status',
            [
                'headers' => ['Accept' => 'application/json'],
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
