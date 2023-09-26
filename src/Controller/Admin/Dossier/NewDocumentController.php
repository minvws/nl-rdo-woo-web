<?php

declare(strict_types=1);

namespace App\Controller\Admin\Dossier;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\WithdrawReason;
use App\Exception\DocumentReplaceException;
use App\Form\Document\ReplaceFormType;
use App\Form\Document\WithdrawFormType;
use App\Service\DocumentWorkflow\DocumentWorkflow;
use App\Service\FileUploader;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

/**
 * TODO:
 * This is a temporary name to develop new functionality without breaking the existing DocumentControllers.
 * Should eventually be merged into one.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class NewDocumentController extends AbstractController
{
    public function __construct(
        private readonly DocumentWorkflow $workflow,
        private readonly TranslatorInterface $translator,
        private readonly FileUploader $fileUploader,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('/balie/dossier/{dossierId}/edit/document/{documentId}', name: 'app_admin_document', methods: ['GET', 'POST'])]
    public function document(
        Breadcrumbs $breadcrumbs,
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        #[MapEntity(mapping: ['documentId' => 'documentNr'])] Document $document
    ): Response {
        if (! $dossier->getDocuments()->contains($document)) {
            throw new NotFoundHttpException('Document not found');
        }

        $breadcrumbs->addRouteItem($dossier->getDossierNr(), 'app_admin_dossier', ['dossierId' => $dossier->getDossierNr()]);
        $breadcrumbs->addRouteItem('Documenten', 'app_admin_documents', ['dossierId' => $dossier->getDossierNr()]);

        return $this->render('admin/dossier/document/details.html.twig', [
            'dossier' => $dossier,
            'document' => $document,
            'breadcrumbs' => $breadcrumbs,
            'workflow' => $this->workflow->getStatus($document),
        ]);
    }

    #[Route('/balie/dossier/{dossierId}/edit/document/{documentId}/withdraw', name: 'app_admin_document_withdraw', methods: ['GET', 'POST'])]
    public function withdraw(
        Breadcrumbs $breadcrumbs,
        Request $request,
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        #[MapEntity(mapping: ['documentId' => 'documentNr'])] Document $document
    ): Response {
        if (! $dossier->getDocuments()->contains($document)) {
            throw new NotFoundHttpException('Document not found');
        }

        $breadcrumbs->addRouteItem($dossier->getDossierNr(), 'app_admin_dossier', ['dossierId' => $dossier->getDossierNr()]);
        $breadcrumbs->addRouteItem('Documenten', 'app_admin_documents', ['dossierId' => $dossier->getDossierNr()]);
        $breadcrumbs->addRouteItem(
            $document->getDocumentNr(),
            'app_admin_document',
            ['dossierId' => $dossier->getDossierNr(), 'documentId' => $document->getDocumentNr()]
        );

        $form = $this->createForm(WithdrawFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var WithdrawReason $reason */
            $reason = $form->get('reason')->getData();

            /** @var string $explanation */
            $explanation = $form->get('explanation')->getData();

            $this->workflow->withdraw($document, $reason, $explanation);
        }

        return $this->render('admin/dossier/document/withdraw.html.twig', [
            'dossier' => $dossier,
            'document' => $document,
            'breadcrumbs' => $breadcrumbs,
            'form' => $form,
            'workflow' => $this->workflow->getStatus($document),
        ]);
    }

    #[Route('/balie/dossier/{dossierId}/edit/document/{documentId}/replace', name: 'app_admin_document_replace', methods: ['GET', 'POST'])]
    public function replace(
        Breadcrumbs $breadcrumbs,
        Request $request,
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        #[MapEntity(mapping: ['documentId' => 'documentNr'])] Document $document
    ): Response {
        if (! $dossier->getDocuments()->contains($document)) {
            throw new NotFoundHttpException('Document not found');
        }

        $status = $this->workflow->getStatus($document);
        if (! $status->canReplace()) {
            throw new AccessDeniedException('Replace action not available for this document');
        }

        $breadcrumbs->addRouteItem($dossier->getDossierNr(), 'app_admin_dossier', ['dossierId' => $dossier->getDossierNr()]);
        $breadcrumbs->addRouteItem('Documenten', 'app_admin_documents', ['dossierId' => $dossier->getDossierNr()]);
        $breadcrumbs->addRouteItem(
            $document->getDocumentNr(),
            'app_admin_document',
            ['dossierId' => $dossier->getDossierNr(), 'documentId' => $document->getDocumentNr()]
        );

        $error = '';
        $replaced = false;

        $form = $this->createForm(ReplaceFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedFile = $form->get('document')->getData();
            if ($uploadedFile instanceof UploadedFile) {
                try {
                    $this->workflow->replace($dossier, $document, $uploadedFile);
                    $replaced = true;
                } catch (DocumentReplaceException $exception) {
                    $error = $this->translator->trans(
                        $exception->getMessage(),
                        [
                            'filename' => $exception->getFilename(),
                            'documentnr' => $exception->getDocument()->getDocumentId(),
                        ]
                    );
                }
            }
        }

        return $this->render('admin/dossier/document/replace.html.twig', [
            'dossier' => $dossier,
            'document' => $document,
            'breadcrumbs' => $breadcrumbs,
            'form' => $form,
            'workflow' => $status,
            'replaced' => $replaced,
            'error' => $error,
        ]);
    }

    #[Route('/balie/dossier/{dossierId}/edit/document/{documentId}/republish', name: 'app_admin_document_republish', methods: ['GET'])]
    public function republish(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        #[MapEntity(mapping: ['documentId' => 'documentNr'])] Document $document
    ): Response {
        if (! $dossier->getDocuments()->contains($document)) {
            throw new NotFoundHttpException('Document not found');
        }

        $this->workflow->republish($document);

        return $this->redirectToRoute('app_admin_document', ['dossierId' => $dossier->getDossierNr(), 'documentId' => $document->getDocumentNr()]);
    }

    #[Route('/balie/dossier/{dossierId}/upload-status', name: 'app_admin_document_upload_status', methods: ['POST'])]
    public function uploadStatus(
        Request $request,
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
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

        return $this->render('admin/dossier/document/upload-status.html.twig', [
            'dossier' => $dossier,
            'uploadStatus' => $dossier->getUploadStatus(),
        ]);
    }
}
