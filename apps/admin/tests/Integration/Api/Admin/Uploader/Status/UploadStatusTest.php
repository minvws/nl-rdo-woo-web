<?php

declare(strict_types=1);

namespace Admin\Tests\Integration\Api\Admin\Uploader\Status;

use Admin\Tests\Integration\Api\Admin\AdminApiTestCase;
use Shared\Domain\Upload\UploadStatus;
use Shared\Tests\Factory\UploadEntityFactory;
use Shared\Tests\Factory\UserFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function sprintf;

final class UploadStatusTest extends AdminApiTestCase
{
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
            ->create();

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
            ->create();

        $userB = UserFactory::new()
            ->asDossierAdmin()
            ->isEnabled()
            ->create();

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
            ->create();

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_GET,
                '/balie/api/uploader/upload/non-existent-upload-id/status',
            );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
