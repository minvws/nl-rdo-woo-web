<?php

declare(strict_types=1);

namespace App\Controller\Admin\Dossier;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\DossierDispatcher;
use App\Domain\Publication\Dossier\Step\StepActionHelper;
use App\Domain\Publication\Dossier\Step\StepName;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionDispatcher;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Entity\WithdrawReason;
use App\Form\Document\WithdrawFormType;
use App\Form\Dossier\DeleteFormType;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DossierActionController extends AbstractController
{
    public function __construct(
        private readonly StepActionHelper $stepHelper,
        private readonly DossierWorkflowManager $dossierWorkflowManager,
        private readonly DossierDispatcher $dossierDispatcher,
        private readonly WooDecisionDispatcher $wooDecisionDispatcher,
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
            'form' => $form->createView(),
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

        $form = $this->createForm(WithdrawFormType::class);
        $form->handleRequest($request);

        if ($this->stepHelper->isFormCancelled($form)) {
            return $this->stepHelper->redirectToDossier($wooDecision);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var WithdrawReason $reason */
            $reason = $form->get('reason')->getData();

            /** @var string $explanation */
            $explanation = $form->get('explanation')->getData();

            $this->wooDecisionDispatcher->dispatchWithDrawAllDocumentsCommand($wooDecision, $reason, $explanation);

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
