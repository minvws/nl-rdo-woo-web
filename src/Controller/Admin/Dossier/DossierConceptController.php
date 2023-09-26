<?php

declare(strict_types=1);

namespace App\Controller\Admin\Dossier;

use App\Entity\Dossier;
use App\Form\Dossier\DecisionType;
use App\Form\Dossier\DetailsType;
use App\Form\Dossier\DocumentUploadType;
use App\Form\Dossier\InventoryType;
use App\Form\Dossier\PublishType;
use App\Service\DossierWorkflow\DossierWorkflow;
use App\Service\DossierWorkflow\DossierWorkflowStatus;
use App\Service\DossierWorkflow\StepName;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DossierConceptController extends AbstractController
{
    public function __construct(
        private readonly DossierWorkflow $workflow,
    ) {
    }

    #[Route('/balie/dossier/concept/create', name: 'app_admin_dossier_concept_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $dossier = new Dossier();

        $form = $this->createForm(DetailsType::class, $dossier);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->workflow->create($dossier);

            $workflowStatus = $this->workflow->getStatus($dossier, StepName::DETAILS);
            $currentStep = $workflowStatus->getCurrentStep();
            $nextStep = $workflowStatus->getNextStep();

            /** @var SubmitButton $next */
            $next = $form->get('next');
            if ($next->isClicked()) {
                return $this->redirectToRoute($nextStep->getRouteName(), ['dossierId' => $dossier->getDossierNr()]);
            }

            return $this->redirectToRoute($currentStep->getRouteName(), ['dossierId' => $dossier->getDossierNr()]);
        }

        return $this->render('admin/dossier/concept/details.html.twig', [
            'dossier' => $dossier,
            'workflowStatus' => $this->workflow->getStatus($dossier),
            'form' => $form,
        ]);
    }

    #[Route('/balie/dossier/{dossierId}/concept/details', name: 'app_admin_dossier_concept_details', methods: ['GET', 'POST'])]
    public function details(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        Request $request,
    ): Response {
        $workflowStatus = $this->workflow->getStatus($dossier, StepName::DETAILS);
        $currentStep = $workflowStatus->getCurrentStep();
        $nextStep = $workflowStatus->getNextStep();

        if (! $workflowStatus->isConcept()) {
            return $this->redirectToRoute($currentStep->getEditRouteName(), ['dossierId' => $dossier->getDossierNr()]);
        }

        $form = $this->createForm(DetailsType::class, $dossier);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->workflow->updateDetails($dossier);

            /** @var SubmitButton $next */
            $next = $form->get('next');
            if ($next->isClicked()) {
                return $this->redirectToRoute($nextStep->getRouteName(), ['dossierId' => $dossier->getDossierNr()]);
            }

            return $this->redirectToRoute($currentStep->getRouteName(), ['dossierId' => $dossier->getDossierNr()]);
        }

        return $this->render('admin/dossier/concept/details.html.twig', [
            'dossier' => $dossier,
            'workflowStatus' => $workflowStatus,
            'form' => $form,
        ]);
    }

    #[Route('/balie/dossier/{dossierId}/concept/decision', name: 'app_admin_dossier_concept_decision', methods: ['GET', 'POST'])]
    public function decision(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        Request $request,
    ): Response {
        $workflowStatus = $this->workflow->getStatus($dossier, StepName::DECISION);
        $currentStep = $workflowStatus->getCurrentStep();

        if (! $workflowStatus->isConcept()) {
            return $this->redirectToRoute($currentStep->getEditRouteName(), ['dossierId' => $dossier->getDossierNr()]);
        }

        if (! $workflowStatus->isReadyForDecision()) {
            return $this->createRedirectToOpenStep($dossier, $workflowStatus);
        }

        $form = $this->createForm(DecisionType::class, $dossier);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->workflow->updateDecision($dossier, $form);

            // Refresh the status after processing, as the first open step depends on this
            $workflowStatus = $this->workflow->getStatus($dossier, StepName::DETAILS);
            $nextStep = $workflowStatus->getFirstOpenStep() ?? $workflowStatus->getNextStep();

            /** @var SubmitButton $next */
            $next = $form->get('next');
            if ($next->isClicked()) {
                return $this->redirectToRoute($nextStep->getRouteName(), ['dossierId' => $dossier->getDossierNr()]);
            }

            return $this->redirectToRoute($currentStep->getRouteName(), ['dossierId' => $dossier->getDossierNr()]);
        }

        return $this->render('admin/dossier/concept/decision.html.twig', [
            'dossier' => $dossier,
            'workflowStatus' => $workflowStatus,
            'form' => $form,
        ]);
    }

    #[Route('/balie/dossier/{dossierId}/concept/documents', name: 'app_admin_dossier_concept_documents', methods: ['GET', 'POST'])]
    public function documents(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        Request $request,
    ): Response {
        $workflowStatus = $this->workflow->getStatus($dossier, StepName::DOCUMENTS);
        $currentStep = $workflowStatus->getCurrentStep();

        if (! $workflowStatus->isConcept()) {
            return $this->redirectToRoute($currentStep->getEditRouteName(), ['dossierId' => $dossier->getDossierNr()]);
        }

        if (! $workflowStatus->isReadyForDocuments()) {
            return $this->createRedirectToOpenStep($dossier, $workflowStatus);
        }

        $inventoryForm = $this->createForm(InventoryType::class);
        $inventoryForm->handleRequest($request);
        if ($inventoryForm->isSubmitted() && $inventoryForm->isValid()) {
            $inventoryResult = $this->workflow->updateInventory($dossier, $inventoryForm);

            if ($inventoryResult->isSuccessful()) {
                return $this->redirectToRoute($currentStep->getRouteName(), ['dossierId' => $dossier->getDossierNr()]);
            }
        }

        $documentForm = $this->createForm(DocumentUploadType::class, $dossier);

        return $this->render('admin/dossier/concept/documents.html.twig', [
            'dossier' => $dossier,
            'workflowStatus' => $workflowStatus,
            'inventoryForm' => $inventoryForm,
            'documentForm' => $documentForm,
        ]);
    }

    #[Route('/balie/dossier/{dossierId}/concept/delete-inventory', name: 'app_admin_dossier_concept_delete_inventory', methods: ['GET'])]
    public function deleteInventory(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
    ): Response {
        $workflowStatus = $this->workflow->getStatus($dossier, StepName::DOCUMENTS);
        $currentStep = $workflowStatus->getCurrentStep();

        if (! $workflowStatus->isConcept()) {
            return $this->redirectToRoute($currentStep->getEditRouteName(), ['dossierId' => $dossier->getDossierNr()]);
        }

        if (! $workflowStatus->isReadyForDocuments()) {
            return $this->createRedirectToOpenStep($dossier, $workflowStatus);
        }

        $this->workflow->removeInventory($dossier);

        return $this->redirectToRoute($currentStep->getRouteName(), ['dossierId' => $dossier->getDossierNr()]);
    }

    #[Route('/balie/dossier/{dossierId}/concept/publish', name: 'app_admin_dossier_concept_publish', methods: ['GET', 'POST'])]
    public function publish(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        Request $request,
    ): Response {
        $workflowStatus = $this->workflow->getStatus($dossier, StepName::PUBLICATION);
        $currentStep = $workflowStatus->getCurrentStep();

        if (! $workflowStatus->isConcept()) {
            return $this->redirectToRoute($currentStep->getEditRouteName(), ['dossierId' => $dossier->getDossierNr()]);
        }

        if (! $workflowStatus->isReadyForPublication()) {
            return $this->createRedirectToOpenStep($dossier, $workflowStatus);
        }

        $form = $this->createForm(PublishType::class, $dossier);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var SubmitButton $preview */
            $preview = $form->get('publish_preview');
            $this->workflow->publish($dossier, $preview->isClicked());

            return $this->redirectToRoute('app_admin_dossier', ['dossierId' => $dossier->getDossierNr()]);
        }

        return $this->render('admin/dossier/concept/publish.html.twig', [
            'dossier' => $dossier,
            'workflowStatus' => $workflowStatus,
            'form' => $form,
        ]);
    }

    private function createRedirectToOpenStep(Dossier $dossier, DossierWorkflowStatus $workflowStatus): RedirectResponse
    {
        $openStep = $workflowStatus->getFirstOpenStep();
        if ($openStep) {
            return $this->redirectToRoute(
                $openStep->getRouteName(),
                ['dossierId' => $dossier->getDossierNr()]
            );
        }

        return $this->redirectToRoute('app_admin_dossier', ['dossierId' => $dossier->getDossierNr()]);
    }
}
