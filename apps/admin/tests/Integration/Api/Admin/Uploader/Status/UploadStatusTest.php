<?php

declare(strict_types=1);

namespace Admin\Tests\Integration\Api\Admin\Uploader\Status;

use Admin\Tests\Integration\Api\Admin\AdminApiTestCase;
use Shared\Domain\Upload\Exception\UploadValidationErrorReason;
use Shared\Domain\Upload\Exception\UploadValidationException;
use Shared\Domain\Upload\UploadEntityStatus;
use Shared\Service\Security\User;
use Shared\Tests\Factory\UploadEntityFactory;
use Shared\Tests\Factory\UserFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function sprintf;
use function Zenstruck\Foundry\Persistence\save;

final class UploadStatusTest extends AdminApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    public function testGetUploadStatusSuccessfully(): void
    {
        $user = $this->createEnabledDossierAdminUser();

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
            'status' => UploadEntityStatus::INCOMPLETE->value,
        ]);
    }

    public function testGetUploadStatusReturnsTheErrorReasonWhenValidationFailed(): void
    {
        $user = $this->createEnabledDossierAdminUser();

        $uploadId = 'foo-123';

        $uploadEntity = UploadEntityFactory::createOne([
            'uploadId' => $uploadId,
            'user' => $user,
        ]);
        $uploadEntity->finishUploading('foo.bar', 123);
        $uploadEntity->failValidation(UploadValidationException::forUnsafeFile());
        save($uploadEntity);

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_GET,
                sprintf('/balie/api/uploader/upload/%s/status', $uploadId),
            );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertJsonContains([
            'uploadId' => $uploadId,
            'status' => UploadEntityStatus::VALIDATION_FAILED->value,
            'errorReason' => UploadValidationErrorReason::UNSAFE_FILE->value,
        ]);
    }

    public function testGetUploadStatusForOtherUsersUploadIsDenied(): void
    {
        $userA = $this->createEnabledDossierAdminUser();

        $userB = $this->createEnabledDossierAdminUser();

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
        $user = $this->createEnabledDossierAdminUser();

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_GET,
                '/balie/api/uploader/upload/non-existent-upload-id/status',
            );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    private function createEnabledDossierAdminUser(): User
    {
        return UserFactory::new()
            ->asDossierAdmin()
            ->isEnabled()
            ->create();
    }
}
