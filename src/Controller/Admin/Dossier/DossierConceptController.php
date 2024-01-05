<?php

declare(strict_types=1);

namespace App\Controller\Admin\Dossier;

use App\Attribute\AuthMatrix;
use App\Entity\Dossier;
use App\Form\Dossier\DecisionType;
use App\Form\Dossier\DetailsType;
use App\Form\Dossier\DocumentUploadType;
use App\Form\Dossier\InventoryType;
use App\Form\Dossier\PublishType;
use App\Form\Dossier\TranslatableFormErrorMapper;
use App\Service\DossierWorkflow\DossierWorkflow;
use App\Service\DossierWorkflow\DossierWorkflowStatus;
use App\Service\DossierWorkflow\StepName;
use App\Service\Security\Authorization\AuthorizationMatrix;
use App\ValueObject\InventoryStatus;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DossierConceptController extends AbstractController
{
    use DossierAuthorizationTrait;

    public function __construct(
        private readonly DossierWorkflow $workflow,
        private readonly AuthorizationMatrix $authorizationMatrix,
        private readonly TranslatableFormErrorMapper $formErrorMapper,
    ) {
    }

    #[Route('/balie/dossier/concept/create', name: 'app_admin_dossier_concept_create', methods: ['GET', 'POST'])]
    #[AuthMatrix('dossier.create')]
    public function create(Request $request): Response
    {
        $dossier = new Dossier();
        $dossier->setPublicationReason(Dossier::REASON_WOO_REQUEST);
        $dossier->setOrganisation($this->authorizationMatrix->getActiveOrganisation());

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
            'workflowStatus' => $this->workflow->getStatus($dossier, StepName::DETAILS),
            'form' => $form,
        ]);
    }

    #[Route('/balie/dossier/{dossierId}/concept/details', name: 'app_admin_dossier_concept_details', methods: ['GET', 'POST'])]
    #[AuthMatrix('dossier.create')]
    public function details(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        Request $request,
    ): Response {
        $this->testIfDossierIsAllowedByUser($dossier);

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
    #[AuthMatrix('dossier.create')]
    public function decision(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        Request $request,
    ): Response {
        $this->testIfDossierIsAllowedByUser($dossier);

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
    #[AuthMatrix('dossier.create')]
    public function documents(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        Request $request,
    ): Response {
        $this->testIfDossierIsAllowedByUser($dossier);

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
            $this->workflow->updateInventory($dossier, $inventoryForm);
        }

        $processRun = $dossier->getProcessRun();
        if ($processRun && $processRun->isFailed()) {
            $this->formErrorMapper->mapRunErrorsToForm($processRun, $inventoryForm);
        }

        $documentForm = $this->createForm(DocumentUploadType::class, $dossier);

        $dataPath = null;
        if ($processRun?->isNotFinal()) {
            $dataPath = 'app_admin_dossier_concept_inventory_status';
        }

        return $this->render('admin/dossier/concept/documents.html.twig', [
            'dossier' => $dossier,
            'processRun' => $processRun,
            'workflowStatus' => $workflowStatus,
            'inventoryForm' => $inventoryForm,
            'documentForm' => $documentForm,
            'dataPath' => $dataPath,
            'inventoryStatus' => new InventoryStatus($dossier),
        ]);
    }

    #[Route('/balie/dossier/{dossierId}/concept/inventory-status', name: 'app_admin_dossier_concept_inventory_status', methods: ['GET'])]
    #[AuthMatrix('dossier.create')]
    public function inventoryProcess(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
    ): Response {
        $this->testIfDossierIsAllowedByUser($dossier);

        $inventoryForm = $this->createForm(InventoryType::class);

        $processRun = $dossier->getProcessRun();
        if ($processRun && $processRun->isFailed()) {
            $this->formErrorMapper->mapRunErrorsToForm($processRun, $inventoryForm);
        }

        $inventoryStatus = new InventoryStatus($dossier);

        return new JsonResponse([
            'content' => $this->renderView('admin/dossier/form/processrun.html.twig', [
                'dossier' => $dossier,
                'processRun' => $processRun,
                'inventoryForm' => $inventoryForm,
                'inventoryStatus' => $inventoryStatus,
                'ajax' => true,
            ]),
            'inventoryStatus' => [
                'canUpload' => $inventoryStatus->canUpload(),
                'hasErrors' => $inventoryStatus->hasErrors(),
                'isQueued' => $inventoryStatus->isQueued(),
                'isRunning' => $inventoryStatus->isRunning(),
                'needsUpdate' => $inventoryStatus->needsUpdate(),
            ],
        ]);
    }

    #[Route('/balie/dossier/{dossierId}/concept/delete-inventory', name: 'app_admin_dossier_concept_delete_inventory', methods: ['GET'])]
    #[AuthMatrix('dossier.create')]
    public function deleteInventory(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
    ): Response {
        $this->testIfDossierIsAllowedByUser($dossier);

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
    #[AuthMatrix('dossier.create')]
    public function publish(
        #[MapEntity(mapping: ['dossierId' => 'dossierNr'])] Dossier $dossier,
        Request $request,
    ): Response {
        $this->testIfDossierIsAllowedByUser($dossier);

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
            $this->workflow->publish($dossier);

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
