<?php

declare(strict_types=1);

namespace App\Tests\Integration\Api\Admin\Uploader\Status;

use App\Domain\Upload\UploadStatus;
use App\Tests\Factory\UploadEntityFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Integration\Api\Admin\AdminApiTestCase;
use App\Tests\Integration\IntegrationTestTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class UploadStatusTest extends AdminApiTestCase
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

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_GET,
                sprintf('/balie/api/uploader/upload/%s/status', $uploadId),
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

        self::createAdminApiClient($userB)
            ->request(
                Request::METHOD_GET,
                sprintf('/balie/api/uploader/upload/%s/status', $uploadId),
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

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_GET,
                '/balie/api/uploader/upload/non-existent-upload-id/status',
            );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
