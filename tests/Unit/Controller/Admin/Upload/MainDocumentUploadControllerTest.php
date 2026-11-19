<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Controller\Admin\Upload;

use Mockery;
use Shared\Controller\Admin\Upload\MainDocumentUploadController;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
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

final class MainDocumentUploadControllerTest extends UnitTestCase
{
    public function testUpload(): void
    {
        $dossierId = Uuid::fromString($this->getFaker()->uuid());
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

        $dossier = Mockery::mock(Covenant::class);
        $dossier->expects('getId')->andReturn($dossierId);

        $dossierRepository = Mockery::mock(DossierRepository::class);
        $dossierRepository->expects('find')->with($dossierId)->andReturn($dossier);

        $security = Mockery::mock(Security::class);
        $security->expects('getUser')->andReturn($user);
        $security->expects('isGranted')
            ->with(
                UploadService::SECURITY_ATTRIBUTE,
                Mockery::on(static function (UploadRequest $uploadRequest) use ($uploadId, $uploadedFile, $dossierId): bool {
                    return $uploadRequest->uploadId === $uploadId
                        && $uploadRequest->uploadedFile === $uploadedFile
                        && $uploadRequest->groupId === UploadGroupId::MAIN_DOCUMENTS
                        && $uploadRequest->additionalParameters->all() === ['dossierId' => $dossierId->toRfc4122()];
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

        $controller = new MainDocumentUploadController($uploadService, $security, $dossierRepository);

        self::assertSame($jsonResponse, $controller->upload($request, $dossierId));
    }

    public function testUploadWithUnknownDossier(): void
    {
        $dossierId = Uuid::fromString($this->getFaker()->uuid());

        $dossierRepository = Mockery::mock(DossierRepository::class);
        $dossierRepository->expects('find')->with($dossierId)->andReturnNull();

        $security = Mockery::mock(Security::class);
        $security->expects('getUser')->andReturn(Mockery::mock(User::class));

        $controller = new MainDocumentUploadController(
            Mockery::mock(UploadService::class),
            $security,
            $dossierRepository,
        );

        self::expectException(NotFoundHttpException::class);

        $controller->upload(new Request(), $dossierId);
    }

    public function testUploadWithDossierWithoutMainDocument(): void
    {
        $dossierId = Uuid::fromString($this->getFaker()->uuid());

        $dossierRepository = Mockery::mock(DossierRepository::class);
        $dossierRepository->expects('find')->with($dossierId)->andReturn(Mockery::mock(AbstractDossier::class));

        $security = Mockery::mock(Security::class);
        $security->expects('getUser')->andReturn(Mockery::mock(User::class));

        $controller = new MainDocumentUploadController(
            Mockery::mock(UploadService::class),
            $security,
            $dossierRepository,
        );

        self::expectException(NotFoundHttpException::class);

        $controller->upload(new Request(), $dossierId);
    }

    public function testUploadWithoutPermission(): void
    {
        $dossierId = Uuid::fromString($this->getFaker()->uuid());

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

        $dossier = Mockery::mock(Covenant::class);
        $dossier->expects('getId')->andReturn($dossierId);

        $dossierRepository = Mockery::mock(DossierRepository::class);
        $dossierRepository->expects('find')->with($dossierId)->andReturn($dossier);

        $security = Mockery::mock(Security::class);
        $security->expects('getUser')->andReturn(Mockery::mock(User::class));
        $security->expects('isGranted')
            ->with(UploadService::SECURITY_ATTRIBUTE, Mockery::type(UploadRequest::class))
            ->andReturnFalse();

        $controller = new MainDocumentUploadController(
            Mockery::mock(UploadService::class),
            $security,
            $dossierRepository,
        );

        self::expectExceptionObject(UploadException::forNotAllowed());

        $controller->upload($request, $dossierId);
    }
}
