<?php

declare(strict_types=1);

namespace App\Controller\Admin\Dossier;

use App\Attribute\AuthMatrix;
use App\Entity\Document;
use App\Entity\Dossier;
use App\Service\DocumentWorkflow\DocumentWorkflow;
use App\Service\FileUploader;
use App\Service\Security\Authorization\AuthorizationMatrix;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class DocumentController extends AbstractController
{
    use DossierAuthorizationTrait;

    public function __construct(
        private readonly FileUploader $fileUploader,
        private readonly LoggerInterface $logger,
        private readonly DocumentWorkflow $workflow,
        private readonly AuthorizationMatrix $authorizationMatrix,
    ) {
    }

    #[Route('/balie/dossier/{dossierId}/edit/document/{documentId}', name: 'app_admin_document', methods: ['GET', 'POST'])]
    #[AuthMatrix('dossier.update')]
    public function document(
        Breadcrumbs $breadcrumbs,
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        #[MapEntity(expr: 'repository.findOneByDossierNrAndDocumentNr(dossierId,documentId)')] Document $document,
    ): Response {
        $this->testIfDossierIsAllowedByUser($dossier);

        $breadcrumbs->addRouteItem($dossier->getTitle() ?? '', 'app_admin_dossier', ['dossierId' => $dossier->getDossierNr()]);
        $breadcrumbs->addRouteItem('workflow_step_documents', 'app_admin_documents', ['dossierId' => $dossier->getDossierNr()]);
        $breadcrumbs->addItem($document->getFileInfo()->getName() ?? '');

        return $this->render('admin/dossier/document/details.html.twig', [
            'dossier' => $dossier,
            'document' => $document,
            'breadcrumbs' => $breadcrumbs,
            'workflow' => $this->workflow->getStatus($document),
        ]);
    }

    #[Route('/balie/dossier/{dossierId}/upload-status', name: 'app_admin_document_upload_status', methods: ['GET'])]
    #[AuthMatrix('dossier.update')]
    public function uploadStatus(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
    ): Response {
        $uploadStatus = $dossier->getUploadStatus();

        return new JsonResponse([
            'uploadsProcessingContent' => $this->renderView('admin/dossier/document/status-uploads-processing.html.twig', [
                'dossier' => $dossier,
                'uploadStatus' => $uploadStatus,
            ]),
            'uploadsRemainingContent' => $this->renderView('admin/dossier/document/status-uploads-remaining.html.twig', [
                'dossier' => $dossier,
                'uploadStatus' => $uploadStatus,
            ]),
            'completed' => $uploadStatus->isComplete(),
        ]);
    }

    #[Route('/balie/dossier/{dossierId}/documents', name: 'app_admin_dossier_documents_upload', methods: ['POST'])]
    #[AuthMatrix('document.update')]
    public function upload(
        Request $request,
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
    ): Response {
        $this->testIfDossierIsAllowedByUser($dossier);

        try {
            $completed = $this->fileUploader->handleUpload($request, $dossier);
        } catch (\Exception $e) {
            $this->logger->error('Error while uploading file(chunk)', [
                'exception' => $e,
                'dossier_id' => $dossier->getDossierNr(),
            ]);

            return new Response('File could not be uploaded correctly', Response::HTTP_BAD_REQUEST);
        }

        if (! $completed) {
            return new Response('Chunk uploaded successfully', Response::HTTP_OK);
        }

        return new Response('File uploaded successfully', Response::HTTP_OK);
    }
}
