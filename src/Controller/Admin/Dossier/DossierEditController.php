<?php

declare(strict_types=1);

namespace App\Controller\Admin\Dossier;

use App\Entity\Dossier;
use App\Form\Dossier\DecisionType;
use App\Form\Dossier\DetailsType;
use App\Form\Dossier\InventoryType;
use App\Form\Dossier\PublishType;
use App\Service\DossierWorkflow\DossierWorkflow;
use App\Service\DossierWorkflow\StepName;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DossierEditController extends AbstractController
{
    public function __construct(
        private readonly DossierWorkflow $workflow,
    ) {
    }

    #[Route('/balie/dossier/{dossierId}/edit/details', name: 'app_admin_dossier_edit_details', methods: ['GET', 'POST'])]
    public function details(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        Request $request,
    ): Response {
        $workflowStatus = $this->workflow->getStatus($dossier, StepName::DETAILS);
        $currentStep = $workflowStatus->getCurrentStep();

        if ($workflowStatus->isConcept()) {
            return $this->redirectToRoute($currentStep->getConceptRouteName(), ['dossierId' => $dossier->getDossierNr()]);
        }

        $form = $this->createForm(DetailsType::class, $dossier);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var SubmitButton $cancel */
            $cancel = $form->get('cancel');
            if (! $cancel->isClicked()) {
                $this->workflow->updateDetails($dossier);
            }

            return $this->redirectToRoute('app_admin_dossier', ['dossierId' => $dossier->getDossierNr()]);
        }

        return $this->render('admin/dossier/edit/details.html.twig', [
            'dossier' => $dossier,
            'workflowStatus' => $workflowStatus,
            'form' => $form,
        ]);
    }

    #[Route('/balie/dossier/{dossierId}/edit/decision', name: 'app_admin_dossier_edit_decision', methods: ['GET', 'POST'])]
    public function decision(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        Request $request,
    ): Response {
        $workflowStatus = $this->workflow->getStatus($dossier, StepName::DECISION);
        $currentStep = $workflowStatus->getCurrentStep();

        if ($workflowStatus->isConcept()) {
            return $this->redirectToRoute($currentStep->getConceptRouteName(), ['dossierId' => $dossier->getDossierNr()]);
        }

        $form = $this->createForm(DecisionType::class, $dossier);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var SubmitButton $cancel */
            $cancel = $form->get('cancel');
            if (! $cancel->isClicked()) {
                $this->workflow->updateDecision($dossier, $form);
            }

            return $this->redirectToRoute('app_admin_dossier', ['dossierId' => $dossier->getDossierNr()]);
        }

        return $this->render('admin/dossier/edit/decision.html.twig', [
            'dossier' => $dossier,
            'workflowStatus' => $workflowStatus,
            'form' => $form,
        ]);
    }

    #[Route('/balie/dossier/{dossierId}/edit/documents', name: 'app_admin_documents', methods: ['GET', 'POST'])]
    public function documents(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        Request $request,
    ): Response {
        $workflowStatus = $this->workflow->getStatus($dossier, StepName::DOCUMENTS);
        $currentStep = $workflowStatus->getCurrentStep();

        if ($workflowStatus->isConcept()) {
            return $this->redirectToRoute($currentStep->getConceptRouteName(), ['dossierId' => $dossier->getDossierNr()]);
        }

        $form = $this->createForm(InventoryType::class, $dossier);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $inventoryResult = $this->workflow->updateInventory($dossier, $form);

            if ($inventoryResult->isSuccessful()) {
                return $this->redirectToRoute($currentStep->getRouteName(), ['dossierId' => $dossier->getDossierNr()]);
            }
        }

        return $this->render('admin/dossier/edit/documents.html.twig', [
            'dossier' => $dossier,
            'workflowStatus' => $workflowStatus,
            'form' => $form,
        ]);
    }

    #[Route('/balie/dossier/{dossierId}/edit/publication', name: 'app_admin_dossier_edit_publication', methods: ['GET', 'POST'])]
    public function publication(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        Request $request,
    ): Response {
        $workflowStatus = $this->workflow->getStatus($dossier, StepName::PUBLICATION);
        $currentStep = $workflowStatus->getCurrentStep();

        if ($workflowStatus->isConcept()) {
            return $this->redirectToRoute($currentStep->getConceptRouteName(), ['dossierId' => $dossier->getDossierNr()]);
        }

        $form = $this->createForm(PublishType::class, $dossier);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $asPreview = false;
            if ($form->has('publish_preview')) {
                /** @var SubmitButton $previewButton */
                $previewButton = $form->get('publish_preview');
                $asPreview = $previewButton->isClicked();
            }

            $this->workflow->publish($dossier, $asPreview);

            return $this->redirectToRoute('app_admin_dossier', ['dossierId' => $dossier->getDossierNr()]);
        }

        return $this->render('admin/dossier/edit/publication.html.twig', [
            'dossier' => $dossier,
            'workflowStatus' => $workflowStatus,
            'form' => $form,
        ]);
    }
}
