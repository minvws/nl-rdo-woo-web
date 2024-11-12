<?php

declare(strict_types=1);

namespace App\Controller\Admin\Dossier\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\DocumentDispatcher;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Entity\Document;
use App\Entity\WithdrawReason;
use App\Exception\DocumentReplaceException;
use App\Form\Document\ReplaceFormType;
use App\Form\Document\WithdrawFormType;
use App\Service\DocumentWorkflow\DocumentWorkflow;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DocumentActionController extends AbstractController
{
    public function __construct(
        private readonly DocumentWorkflow $workflow,
        private readonly TranslatorInterface $translator,
        private readonly DocumentDispatcher $dispatcher,
    ) {
    }

    #[Route(
        path: '/balie/dossier/woodecision/document/withdraw/{prefix}/{dossierId}/{documentId}',
        name: 'app_admin_dossier_woodecision_document_withdraw',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('AuthMatrix.dossier.update', subject: 'dossier')]
    public function withdraw(
        Breadcrumbs $breadcrumbs,
        Request $request,
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] WooDecision $dossier,
        #[MapEntity(expr: 'repository.findOneByDossierNrAndDocumentNr(prefix, dossierId,documentId)')] Document $document,
    ): Response {
        $breadcrumbs->addRouteItem(
            $dossier->getDossierNr(),
            'app_admin_dossier',
            ['prefix' => $dossier->getDocumentPrefix(), 'dossierId' => $dossier->getDossierNr()]
        );
        $breadcrumbs->addRouteItem(
            'Documenten',
            'app_admin_dossier_woodecision_documents_edit',
            ['prefix' => $dossier->getDocumentPrefix(), 'dossierId' => $dossier->getDossierNr()],
        );
        $breadcrumbs->addRouteItem(
            $document->getDocumentNr(),
            'app_admin_dossier_woodecision_document',
            ['prefix' => $dossier->getDocumentPrefix(), 'dossierId' => $dossier->getDossierNr(), 'documentId' => $document->getDocumentNr()]
        );
        $breadcrumbs->addItem('admin.dossiers.woo-decision.step.withdraw_document');

        $form = $this->createForm(WithdrawFormType::class);
        $form->handleRequest($request);

        // When the cancel button is clicked, redirect back to the dossier
        /** @var SubmitButton $cancelButton */
        $cancelButton = $form->get('cancel');
        if ($cancelButton->isClicked()) {
            return $this->redirectToRoute('app_admin_dossier_woodecision_document', [
                'prefix' => $dossier->getDocumentPrefix(),
                'dossierId' => $dossier->getDossierNr(),
                'documentId' => $document->getDocumentNr(),
            ]);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var WithdrawReason $reason */
            $reason = $form->get('reason')->getData();

            /** @var string $explanation */
            $explanation = $form->get('explanation')->getData();

            $this->dispatcher->dispatchWithDrawDocumentCommand($dossier, $document, $reason, $explanation);
        }

        return $this->render('admin/dossier/woo-decision/document/withdraw.html.twig', [
            'dossier' => $dossier,
            'document' => $document,
            'breadcrumbs' => $breadcrumbs,
            'form' => $form->createView(),
            'workflow' => $this->workflow->getStatus($document),
        ]);
    }

    #[Route(
        path: '/balie/dossier/woodecision/document/replace/{prefix}/{dossierId}/{documentId}',
        name: 'app_admin_dossier_woodecision_document_replace',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('AuthMatrix.dossier.update', subject: 'dossier')]
    public function replace(
        Breadcrumbs $breadcrumbs,
        Request $request,
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] WooDecision $dossier,
        #[MapEntity(expr: 'repository.findOneByDossierNrAndDocumentNr(prefix, dossierId,documentId)')] Document $document,
    ): Response {
        $status = $this->workflow->getStatus($document);
        if (! $status->canReplace()) {
            return $this->redirectToRoute('app_admin_dossier_woodecision_document', [
                'prefix' => $dossier->getDocumentPrefix(),
                'dossierId' => $dossier->getDossierNr(),
                'documentId' => $document->getDocumentNr(),
            ]);
        }

        $breadcrumbs->addRouteItem(
            $dossier->getDossierNr(),
            'app_admin_dossier',
            ['prefix' => $dossier->getDocumentPrefix(), 'dossierId' => $dossier->getDossierNr()]
        );
        $breadcrumbs->addRouteItem(
            'Documenten',
            'app_admin_dossier_woodecision_documents_edit',
            ['prefix' => $dossier->getDocumentPrefix(), 'dossierId' => $dossier->getDossierNr()],
        );
        $breadcrumbs->addRouteItem(
            $document->getDocumentNr(),
            'app_admin_dossier_woodecision_document',
            ['prefix' => $dossier->getDocumentPrefix(), 'dossierId' => $dossier->getDossierNr(), 'documentId' => $document->getDocumentNr()]
        );
        $breadcrumbs->addItem('admin.dossiers.woo-decision.step.replace_document');

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

        return $this->render('admin/dossier/woo-decision/document/replace.html.twig', [
            'dossier' => $dossier,
            'document' => $document,
            'breadcrumbs' => $breadcrumbs,
            'form' => $form->createView(),
            'workflow' => $status,
            'replaced' => $replaced,
            'error' => $error,
        ]);
    }
}
