<?php

declare(strict_types=1);

namespace App\Controller\Admin\Dossier\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Entity\Document;
use App\Service\DocumentWorkflow\DocumentWorkflow;
use App\Service\FileUploader;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class DocumentController extends AbstractController
{
    public function __construct(
        private readonly FileUploader $fileUploader,
        private readonly LoggerInterface $logger,
        private readonly DocumentWorkflow $workflow,
    ) {
    }

    #[Route(
        path: '/balie/dossier/woodecision/document/summary/{prefix}/{dossierId}/{documentId}',
        name: 'app_admin_dossier_woodecision_document',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('AuthMatrix.dossier.update', subject: 'dossier')]
    public function document(
        Breadcrumbs $breadcrumbs,
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] WooDecision $dossier,
        #[MapEntity(expr: 'repository.findOneByDossierNrAndDocumentNr(prefix, dossierId,documentId)')] Document $document,
    ): Response {
        $breadcrumbs->addRouteItem(
            $dossier->getTitle() ?? '',
            'app_admin_dossier',
            ['prefix' => $dossier->getDocumentPrefix(), 'dossierId' => $dossier->getDossierNr()]
        );
        $breadcrumbs->addRouteItem(
            'admin.dossiers.woo-decision.step.documents',
            'app_admin_dossier_woodecision_documents_edit',
            ['prefix' => $dossier->getDocumentPrefix(), 'dossierId' => $dossier->getDossierNr()]
        );
        $breadcrumbs->addItem($document->getFileInfo()->getName() ?? '');

        return $this->render('admin/dossier/woo-decision/document/details.html.twig', [
            'dossier' => $dossier,
            'document' => $document,
            'breadcrumbs' => $breadcrumbs,
            'workflow' => $this->workflow->getStatus($document),
        ]);
    }

    #[Route(
        path: '/balie/dossier/woodecision/document/upload-status/{prefix}/{dossierId}',
        name: 'app_admin_dossier_woodecision_document_upload_status',
        methods: ['GET'],
    )]
    #[IsGranted('AuthMatrix.dossier.update', subject: 'dossier')]
    public function uploadStatus(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] WooDecision $dossier,
    ): Response {
        $uploadStatus = $dossier->getUploadStatus();

        return new JsonResponse([
            'uploadsProcessingContent' => $this->renderView('admin/dossier/woo-decision/document/status-uploads-processing.html.twig', [
                'dossier' => $dossier,
                'uploadStatus' => $uploadStatus,
            ]),
            'uploadsRemainingContent' => $this->renderView('admin/dossier/woo-decision/document/status-uploads-remaining.html.twig', [
                'dossier' => $dossier,
                'uploadStatus' => $uploadStatus,
            ]),
            'completed' => $uploadStatus->isComplete(),
        ]);
    }

    #[Route(
        path: '/balie/dossier/woodecision/document/upload/{prefix}/{dossierId}',
        name: 'app_admin_dossier_woodecision_document_upload',
        methods: ['POST'],
    )]
    #[IsGranted('AuthMatrix.document.update', subject: 'dossier')]
    public function upload(
        Request $request,
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] WooDecision $dossier,
    ): Response {
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
