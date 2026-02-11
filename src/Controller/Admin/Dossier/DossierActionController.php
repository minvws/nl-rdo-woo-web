<?php

declare(strict_types=1);

namespace Shared\Controller\Admin\Dossier;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\DossierDispatcher;
use Shared\Domain\Publication\Dossier\Step\StepActionHelper;
use Shared\Domain\Publication\Dossier\Step\StepName;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawReason;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionDispatcher;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use Shared\Form\Document\WithdrawDocumentFormType;
use Shared\Form\Dossier\DeleteFormType;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

class DossierActionController extends AbstractController
{
    public function __construct(
        private readonly StepActionHelper $stepHelper,
        private readonly DossierWorkflowManager $dossierWorkflowManager,
        private readonly DossierDispatcher $dossierDispatcher,
        private readonly WooDecisionDispatcher $wooDecisionDispatcher,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route('/balie/dossier/delete/{prefix}/{dossierId}', name: 'app_admin_dossier_delete', methods: ['GET', 'POST'])]
    #[IsGranted('AuthMatrix.dossier.delete', subject: 'dossier')]
    public function delete(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] AbstractDossier $dossier,
        Request $request,
        Breadcrumbs $breadcrumbs,
    ): Response {
        if (! $this->dossierWorkflowManager->isTransitionAllowed($dossier, DossierStatusTransition::DELETE)) {
            return $this->stepHelper->redirectToDossier($dossier);
        }

        $success = false;
        $form = $this->createForm(DeleteFormType::class);
        $form->handleRequest($request);

        if ($this->stepHelper->isFormCancelled($form)) {
            return $this->stepHelper->redirectToDossier($dossier);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $this->dossierDispatcher->dispatchDeleteDossierCommand($dossier->getId());

            $success = true;
        }

        $this->stepHelper->addDossierToBreadcrumbs($breadcrumbs, $dossier, 'admin.dossier.delete.title');

        return $this->render('admin/dossier/delete.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'form' => $form,
            'success' => $success,
        ]);
    }

    #[Route('/balie/dossier/documenten-intrekken/{prefix}/{dossierId}', name: 'app_admin_dossier_withdraw_all_documents', methods: ['GET', 'POST'])]
    #[IsGranted('AuthMatrix.dossier.update', subject: 'wooDecision')]
    public function withdrawAllDocuments(
        #[MapEntity(mapping: ['prefix' => 'documentPrefix', 'dossierId' => 'dossierNr'])] WooDecision $wooDecision,
        Request $request,
        Breadcrumbs $breadcrumbs,
    ): Response {
        $wizardStatus = $this->stepHelper->getWizardStatus($wooDecision, StepName::DOCUMENTS);
        if (! $wizardStatus->isCurrentStepAccessibleInEditMode()) {
            return $this->stepHelper->redirectToDossier($wooDecision);
        }

        $form = $this->createForm(WithdrawDocumentFormType::class);
        $form->handleRequest($request);

        if ($this->stepHelper->isFormCancelled($form)) {
            return $this->stepHelper->redirectToDossier($wooDecision);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var DocumentWithdrawReason $reason */
            $reason = $form->get('reason')->getData();

            /** @var string $explanation */
            $explanation = $form->get('explanation')->getData();

            $this->wooDecisionDispatcher->dispatchWithDrawAllDocumentsCommand($wooDecision, $reason, $explanation);

            $this->addFlash(
                'backend',
                ['success' => $this->translator->trans('admin.dossiers.action.withdraw_all_executing')]
            );

            return $this->redirectToRoute(
                'app_admin_dossier_woodecision_documents_edit',
                ['prefix' => $wooDecision->getDocumentPrefix(), 'dossierId' => $wooDecision->getDossierNr()]
            );
        }

        $this->stepHelper->addDossierToBreadcrumbs($breadcrumbs, $wooDecision, 'admin.dossiers.decision.documents.withdraw_all');

        return $this->render('admin/dossier/woo-decision/withdraw-all-documents.html.twig', [
            'breadcrumbs' => $breadcrumbs,
            'form' => $form,
        ]);
    }
}
