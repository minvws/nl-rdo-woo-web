<?php

declare(strict_types=1);

namespace Shared\Controller\Admin\Upload;

use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\MainDocument\EntityWithMainDocument;
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

final class MainDocumentUploadController extends AbstractController
{
    public const string ROUTE_NAME_UPLOAD = 'app_admin_upload_main_documents';

    public function __construct(
        private readonly UploadService $uploadService,
        private readonly Security $security,
        private readonly DossierRepository $dossierRepository,
    ) {
    }

    #[Route('/balie/upload/main-documents/{dossierId}', self::ROUTE_NAME_UPLOAD, methods: ['POST'], format: 'json')]
    #[IsGranted('AuthMatrix.upload.create')]
    public function upload(Request $request, Uuid $dossierId): JsonResponse
    {
        $user = $this->security->getUser();
        Assert::isInstanceOf($user, User::class);

        $dossier = $this->dossierRepository->find($dossierId);
        if (! $dossier instanceof EntityWithMainDocument) {
            throw $this->createNotFoundException();
        }

        $uploadRequest = UploadRequest::fromHttpRequestForGroup(
            $request,
            UploadGroupId::MAIN_DOCUMENTS,
            new InputBag(['dossierId' => $dossier->getId()->toRfc4122()]),
        );

        if (! $this->security->isGranted(UploadService::SECURITY_ATTRIBUTE, $uploadRequest)) {
            throw UploadException::forNotAllowed();
        }

        $result = $this->uploadService->handleUpload($uploadRequest, $user);

        return $result->toJsonResponse();
    }
}
