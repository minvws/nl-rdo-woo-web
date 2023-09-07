<?php

declare(strict_types=1);

namespace App\Controller\Admin\Dossier;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\WithdrawReason;
use App\Form\Document\IngestFormType;
use App\Form\Document\RemoveFormType;
use App\Form\Document\WithdrawFormType;
use App\Form\Dossier\DocumentUploadType;
use App\Service\DocumentService;
use App\Service\FileUploader;
use App\Service\Ingest\IngestService;
use App\Service\Ingest\Options;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DocumentController extends AbstractController
{
    public function __construct(
        private readonly DocumentService $documentService,
        private readonly IngestService $ingester,
        private readonly FileUploader $fileUploader,
        private readonly TranslatorInterface $translator
    ) {
    }

    #[Route('/balie/dossier/{dossierId}/documents', name: 'app_admin_dossier_documents_edit', methods: ['GET'])]
    public function docEdit(
        Breadcrumbs $breadcrumbs,
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier
    ): Response {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Admin', 'app_admin');
        $breadcrumbs->addRouteItem('Dossier management', 'app_admin_dossiers');
        $breadcrumbs->addItem('Documents');

        $form = $this->createForm(DocumentUploadType::class, $dossier, ['csrf_protection' => false]);

        return $this->render('admin/dossier/documents.html.twig', [
            'dossier' => $dossier,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/balie/dossier/{dossierId}/documents', name: 'app_admin_dossier_documents_upload', methods: ['POST'])]
    public function upload(
        Request $request,
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
    ): Response {
        try {
            $completed = $this->fileUploader->handleUpload($request, $dossier);
        } catch (\Exception $e) {
            // @TODO: do we want to send the message directly to the user?
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        if (! $completed) {
            return new Response('Chunk uploaded successfully', Response::HTTP_OK);
        }

        return $this->render('admin/dossier/document-status.html.twig', [
            'dossier' => $dossier,
            'uploadStatus' => $dossier->getUploadStatus(),
        ]);
    }

    #[Route('/balie/dossier/{dossierId}/document/{documentId}', name: 'app_admin_dossier_document_details', methods: ['GET', 'POST'])]
    public function details(
        Breadcrumbs $breadcrumbs,
        Request $request,
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        #[MapEntity(mapping: ['documentId' => 'documentNr'])] Document $document
    ): Response {
        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Admin', 'app_admin');
        $breadcrumbs->addRouteItem('Dossier management', 'app_admin_dossiers');
        $breadcrumbs->addItem('Dossier');

        if (! $dossier->getDocuments()->contains($document)) {
            throw new NotFoundHttpException('Document not found');
        }

        $removeForm = $this->createForm(RemoveFormType::class, $dossier);
        $ingestForm = $this->createForm(IngestFormType::class, $dossier);

        $removeForm->handleRequest($request);
        if ($removeForm->isSubmitted() && $removeForm->isValid()) {
            $this->documentService->removeDocumentFromDossier($dossier, $document);

            $this->addFlash('backend', ['success' => $this->translator->trans('Document has been removed')]);

            return $this->redirectToRoute('app_admin_dossier_edit', ['dossierId' => $dossier->getDossierNr()]);
        }

        $ingestForm->handleRequest($request);
        if ($ingestForm->isSubmitted() && $ingestForm->isValid()) {
            $this->ingester->ingest($document, new Options());

            $this->addFlash('backend', ['success' => $this->translator->trans('Document is scheduled for ingestion')]);

            return $this->redirectToRoute('app_admin_dossier_edit', ['dossierId' => $dossier->getDossierNr()]);
        }

        return $this->render('admin/dossier/document-details.html.twig', [
            'dossier' => $dossier,
            'document' => $document,
            'removeForm' => $removeForm->createView(),
            'ingestForm' => $ingestForm->createView(),
        ]);
    }

    #[Route('/balie/dossier/{dossierId}/document/{documentId}/withdraw', name: 'app_admin_dossier_document_withdraw', methods: ['GET', 'POST'])]
    public function docWithdraw(
        Breadcrumbs $breadcrumbs,
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        #[MapEntity(mapping: ['documentId' => 'documentNr'])] Document $document,
        Request $request,
    ): Response {
        if (! $dossier->getDocuments()->contains($document)) {
            throw new NotFoundHttpException('Document not found');
        }

        if ($document->isWithdrawn()) {
            $this->addFlash('backend', ['error' => $this->translator->trans('Document is already withdrawn')]);

            return $this->redirectToRoute(
                'app_admin_dossier_document_details',
                ['dossierId' => $dossier->getDossierNr(), 'documentId' => $document->getDocumentNr()]
            );
        }

        $form = $this->createForm(WithdrawFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var WithdrawReason $reason */
            $reason = $form->get('reason')->getData();

            /** @var string $explanation */
            $explanation = $form->get('explanation')->getData();

            $this->documentService->withdraw($document, $reason, $explanation);
            $this->addFlash('backend', ['success' => $this->translator->trans('Document has been withdrawn')]);

            return $this->redirectToRoute(
                'app_admin_dossier_document_details',
                ['dossierId' => $dossier->getDossierNr(), 'documentId' => $document->getDocumentNr()]
            );
        }

        $breadcrumbs->addRouteItem('Home', 'app_home');
        $breadcrumbs->addRouteItem('Admin', 'app_admin');
        $breadcrumbs->addRouteItem('Dossier management', 'app_admin_dossiers');
        $breadcrumbs->addRouteItem(
            'Document',
            'app_admin_dossier_document_details',
            ['dossierId' => $dossier->getDossierNr(), 'documentId' => $document->getDocumentNr()]
        );
        $breadcrumbs->addItem('Intrekken');

        return $this->render('admin/dossier/document-withdraw.html.twig', [
            'dossier' => $dossier,
            'document' => $document,
            'form' => $form->createView(),
        ]);
    }
}
