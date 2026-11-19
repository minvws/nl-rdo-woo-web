<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Controller\Admin\Upload;

use Mockery;
use Shared\Controller\Admin\Upload\DepartmentUploadController;
use Shared\Domain\Department\Department;
use Shared\Domain\Department\DepartmentRepository;
use Shared\Domain\Upload\Exception\UploadException;
use Shared\Domain\Upload\Result\UploadResultInterface;
use Shared\Domain\Upload\UploadRequest;
use Shared\Domain\Upload\UploadService;
use Shared\Service\Security\User;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

final class DepartmentUploadControllerTest extends UnitTestCase
{
    public function testUpload(): void
    {
        $departmentId = Uuid::fromString($this->getFaker()->uuid());
        $uploadId = $this->getFaker()->uuid();
        $uploadedFile = Mockery::mock(UploadedFile::class);

        $request = new Request(
            request: [
                'chunkindex' => 0,
                'totalchunkcount' => 1,
                'uuid' => $uploadId,
            ],
            files: [
                'file' => $uploadedFile,
            ],
        );

        $user = Mockery::mock(User::class);

        $department = Mockery::mock(Department::class);
        $department->expects('getId')->andReturn($departmentId);

        $departmentRepository = Mockery::mock(DepartmentRepository::class);
        $departmentRepository->expects('find')->with($departmentId)->andReturn($department);

        $security = Mockery::mock(Security::class);
        $security->expects('getUser')->andReturn($user);
        $security->expects('isGranted')
            ->with(
                UploadService::SECURITY_ATTRIBUTE,
                Mockery::on(static function (UploadRequest $uploadRequest) use ($uploadId, $uploadedFile, $departmentId): bool {
                    return $uploadRequest->uploadId === $uploadId
                        && $uploadRequest->uploadedFile === $uploadedFile
                        && $uploadRequest->groupId === UploadGroupId::DEPARTMENT
                        && $uploadRequest->additionalParameters->all() === ['departmentId' => $departmentId->toRfc4122()];
                }),
            )
            ->andReturnTrue();

        $jsonResponse = new JsonResponse();

        $uploadResult = Mockery::mock(UploadResultInterface::class);
        $uploadResult->expects('toJsonResponse')->andReturn($jsonResponse);

        $uploadService = Mockery::mock(UploadService::class);
        $uploadService->expects('handleUpload')
            ->with(Mockery::type(UploadRequest::class), $user)
            ->andReturn($uploadResult);

        $controller = new DepartmentUploadController($uploadService, $security, $departmentRepository);

        self::assertSame($jsonResponse, $controller->upload($request, $departmentId));
    }

    public function testUploadWithUnknownDepartment(): void
    {
        $departmentId = Uuid::fromString($this->getFaker()->uuid());

        $departmentRepository = Mockery::mock(DepartmentRepository::class);
        $departmentRepository->expects('find')->with($departmentId)->andReturnNull();

        $security = Mockery::mock(Security::class);
        $security->expects('getUser')->andReturn(Mockery::mock(User::class));

        $controller = new DepartmentUploadController(
            Mockery::mock(UploadService::class),
            $security,
            $departmentRepository,
        );

        self::expectException(NotFoundHttpException::class);

        $controller->upload(new Request(), $departmentId);
    }

    public function testUploadWithoutPermission(): void
    {
        $departmentId = Uuid::fromString($this->getFaker()->uuid());

        $request = new Request(
            request: [
                'chunkindex' => 0,
                'totalchunkcount' => 1,
                'uuid' => $this->getFaker()->uuid(),
            ],
            files: [
                'file' => Mockery::mock(UploadedFile::class),
            ],
        );

        $department = Mockery::mock(Department::class);
        $department->expects('getId')->andReturn($departmentId);

        $departmentRepository = Mockery::mock(DepartmentRepository::class);
        $departmentRepository->expects('find')->with($departmentId)->andReturn($department);

        $security = Mockery::mock(Security::class);
        $security->expects('getUser')->andReturn(Mockery::mock(User::class));
        $security->expects('isGranted')
            ->with(UploadService::SECURITY_ATTRIBUTE, Mockery::type(UploadRequest::class))
            ->andReturnFalse();

        $controller = new DepartmentUploadController(
            Mockery::mock(UploadService::class),
            $security,
            $departmentRepository,
        );

        self::expectExceptionObject(UploadException::forNotAllowed());

        $controller->upload($request, $departmentId);
    }
}
