<?php

declare(strict_types=1);

namespace Shared\Controller\Admin\Upload;

use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
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

final class WooDecisionDocumentUploadController extends AbstractController
{
    public const string ROUTE_NAME_UPLOAD = 'app_admin_upload_woo_decision_documents';

    public function __construct(
        private readonly UploadService $uploadService,
        private readonly Security $security,
        private readonly WooDecisionRepository $wooDecisionRepository,
    ) {
    }

    #[Route('/balie/upload/woo-decision-documents/{dossierId}', self::ROUTE_NAME_UPLOAD, methods: ['POST'], format: 'json')]
    #[IsGranted('AuthMatrix.upload.create')]
    public function upload(Request $request, Uuid $dossierId): JsonResponse
    {
        $user = $this->security->getUser();
        Assert::isInstanceOf($user, User::class);

        $wooDecision = $this->wooDecisionRepository->find($dossierId);
        if ($wooDecision === null) {
            throw $this->createNotFoundException();
        }

        $uploadRequest = UploadRequest::fromHttpRequestForGroup(
            $request,
            UploadGroupId::WOO_DECISION_DOCUMENTS,
            new InputBag(['dossierId' => $wooDecision->getId()->toRfc4122()]),
        );

        if (! $this->security->isGranted(UploadService::SECURITY_ATTRIBUTE, $uploadRequest)) {
            throw UploadException::forNotAllowed();
        }

        $result = $this->uploadService->handleUpload($uploadRequest, $user);

        return $result->toJsonResponse();
    }
}
