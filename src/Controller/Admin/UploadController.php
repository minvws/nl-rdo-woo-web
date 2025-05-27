<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Domain\Uploader\UploadRequest;
use App\Domain\Uploader\UploadService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class UploadController extends AbstractController
{
    public function __construct(
        private readonly UploadService $uploadService,
    ) {
    }

    #[Route('/balie/upload', name: 'app_admin_upload', methods: ['POST', 'PUT', 'PATCH'], format: 'json')]
    #[IsGranted('AuthMatrix.dossier.update')]
    public function upload(Request $request): JsonResponse
    {
        $result = $this->uploadService->handleUploadRequest(
            UploadRequest::fromHttpRequest($request)
        );

        return $result->toJsonResponse();
    }
}
