<?php

declare(strict_types=1);

namespace Shared\Controller\Admin;

use Shared\Domain\Upload\Exception\UploadException;
use Shared\Domain\Upload\UploadRequest;
use Shared\Domain\Upload\UploadService;
use Shared\Service\Security\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Webmozart\Assert\Assert;

final class UploadController extends AbstractController
{
    public function __construct(
        private readonly UploadService $uploadService,
        private readonly Security $security,
    ) {
    }

    #[Route('/balie/upload', name: 'app_admin_upload', methods: ['POST', 'PUT', 'PATCH'], format: 'json')]
    #[IsGranted('AuthMatrix.upload.create')]
    public function upload(Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        Assert::isInstanceOf($user, User::class);

        $uploadRequest = UploadRequest::fromHttpRequest($request);
        if (! $this->security->isGranted(UploadService::SECURITY_ATTRIBUTE, $uploadRequest)) {
            throw UploadException::forNotAllowed();
        }

        $result = $this->uploadService->handleUploadRequest($uploadRequest, $user);

        return $result->toJsonResponse();
    }
}
