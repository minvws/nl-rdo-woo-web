<?php

declare(strict_types=1);

namespace Shared\Controller\Admin\Upload;

use Shared\Domain\Department\DepartmentRepository;
use Shared\Domain\Upload\Exception\UploadException;
use Shared\Domain\Upload\UploadRequest;
use Shared\Domain\Upload\UploadService;
use Shared\Service\Security\User;
use Shared\Service\Uploader\UploadGroupId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

final class DepartmentUploadController extends AbstractController
{
    public const string ROUTE_NAME_UPLOAD = 'app_admin_upload_department';

    public function __construct(
        private readonly UploadService $uploadService,
        private readonly Security $security,
        private readonly DepartmentRepository $departmentRepository,
    ) {
    }

    #[Route('/balie/upload/department/{departmentId}', self::ROUTE_NAME_UPLOAD, methods: ['POST'], format: 'json')]
    #[IsGranted('AuthMatrix.upload.create')]
    public function upload(Request $request, Uuid $departmentId): JsonResponse
    {
        $user = $this->security->getUser();
        Assert::isInstanceOf($user, User::class);

        $department = $this->departmentRepository->find($departmentId);
        if ($department === null) {
            throw $this->createNotFoundException();
        }

        $uploadRequest = UploadRequest::fromHttpRequestForGroup(
            $request,
            UploadGroupId::DEPARTMENT,
            new InputBag(['departmentId' => $department->getId()->toRfc4122()]),
        );

        if (! $this->security->isGranted(UploadService::SECURITY_ATTRIBUTE, $uploadRequest)) {
            throw UploadException::forNotAllowed();
        }

        $result = $this->uploadService->handleUpload($uploadRequest, $user);

        return $result->toJsonResponse();
    }
}
