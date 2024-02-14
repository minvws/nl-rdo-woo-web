<?php

declare(strict_types=1);

namespace App\Service\DossierWorkflow;

use App\Entity\Dossier;
use App\Entity\WithdrawReason;
use App\Enum\PublicationStatus;
use App\Service\DossierService;
use App\Service\HistoryService;
use App\Service\Inventory\InventoryService;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * This class ensures executed actions are allowed in the current workflow state. If so, they are forwarded to the relevant services for execution.
 * It also includes some input/output mapping, but the logic for the actions themselves should not be implemented in this class!
 */
class DossierWorkflow
{
    public function __construct(
        private readonly DossierService $dossierService,
        private readonly InventoryService $inventoryService,
        private readonly WorkflowStatusFactory $statusFactory,
        private readonly HistoryService $historyService,
    ) {
    }

    public function getStatus(Dossier $dossier, StepName $currentStep = StepName::DETAILS): DossierWorkflowStatus
    {
        return $this->statusFactory->getWorkflowStatus($dossier, $currentStep);
    }

    public function create(Dossier $dossier): void
    {
        $dossier->setStatus(PublicationStatus::CONCEPT);
        $this->dossierService->updateDetails($dossier);
        $this->historyService->addDossierEntry($dossier, 'dossier_created', []);
    }

    public function updateDetails(Dossier $dossier): void
    {
        $this->dossierService->updateDetails($dossier);
    }

    public function updateDecision(Dossier $dossier, FormInterface $form): void
    {
        if (! $this->getStatus($dossier)->isReadyForDecision()) {
            throw new \RuntimeException('Cannot update decision for this dossier');
        }

        /** @var ?UploadedFile $uploadedFile */
        $uploadedFile = $form->get('decision_document')->getData();
        if ($uploadedFile instanceof UploadedFile) {
            $this->dossierService->updateDecisionDocument($uploadedFile, $dossier);
        }

        $this->dossierService->updateDecision($dossier);
    }

    public function updateInventory(Dossier $dossier, FormInterface $form): void
    {
        if (! $this->getStatus($dossier)->isReadyForDocuments()) {
            throw new \RuntimeException('Cannot update inventory for this dossier');
        }

        $uploadedFile = $form->get('inventory')->getData();
        if (! $uploadedFile instanceof UploadedFile) {
            throw new \RuntimeException('Missing inventory uploadfile');
        }

        $this->dossierService->processInventory($uploadedFile, $dossier);
    }

    public function confirmInventoryUpdate(Dossier $dossier): void
    {
        if (! $this->getStatus($dossier)->isReadyForDocuments()) {
            throw new \RuntimeException('Cannot update inventory for this dossier');
        }

        $this->dossierService->confirmInventoryUpdate($dossier);
    }

    public function rejectInventoryUpdate(Dossier $dossier): void
    {
        if (! $this->getStatus($dossier)->isReadyForDocuments()) {
            throw new \RuntimeException('Cannot update inventory for this dossier');
        }

        $this->dossierService->rejectInventoryUpdate($dossier);
    }

    public function publish(Dossier $dossier): void
    {
        if (! $this->getStatus($dossier)->isReadyForPublication()) {
            throw new \RuntimeException('Cannot publish this dossier');
        }

        $this->dossierService->updatePublication($dossier);
    }

    public function removeInventory(Dossier $dossier): void
    {
        if (! $this->getStatus($dossier)->isReadyForDocuments()) {
            throw new \RuntimeException('Cannot remove inventory for this dossier');
        }

        $this->inventoryService->removeInventories($dossier);

        $this->dossierService->validateCompletion($dossier);
    }

    public function withdrawAllDocuments(Dossier $dossier, WithdrawReason $reason, string $explanation): void
    {
        if ($this->getStatus($dossier)->isConcept()) {
            throw new \RuntimeException('Cannot retract documents for a concept dossier');
        }

        $this->dossierService->withdrawAllDocuments($dossier, $reason, $explanation);
    }

    public function delete(Dossier $dossier): void
    {
        if (! $this->getStatus($dossier)->isConcept()) {
            throw new \RuntimeException('Only concept dossiers can be deleted');
        }

        $this->dossierService->remove($dossier);
    }
}
