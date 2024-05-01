<?php

declare(strict_types=1);

namespace App\Service\DossierWizard;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Command\DeleteDossierCommand;
use App\Domain\Publication\Dossier\DossierPublisher;
use App\Domain\Publication\Dossier\Step\StepName;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowException;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Service\DossierService;
use App\Service\HistoryService;
use App\Service\Inventory\InventoryService;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @todo refactor this to support multiple dossier types: #2133
 *
 * This class ensures executed actions are allowed in the current workflow state. If so, they are forwarded to the relevant services for execution.
 * It also includes some input/output mapping, but the logic for the actions themselves should not be implemented in this class!
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
readonly class DossierWizardHelper
{
    public function __construct(
        private DossierService $dossierService,
        private DossierPublisher $dossierPublisher,
        private InventoryService $inventoryService,
        private WizardStatusFactory $statusFactory,
        private HistoryService $historyService,
        private DossierWorkflowManager $dossierWorkflowManager,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function getStatus(AbstractDossier $dossier, StepName $currentStep = StepName::DETAILS): DossierWizardStatus
    {
        return $this->statusFactory->getWizardStatus($dossier, $currentStep);
    }

    public function create(AbstractDossier $dossier): void
    {
        $this->updateDetails($dossier);
        $this->historyService->addDossierEntry($dossier, 'dossier_created', []);
    }

    public function updateDetails(AbstractDossier $dossier): void
    {
        $this->dossierWorkflowManager->applyTransition($dossier, DossierStatusTransition::UPDATE_DETAILS);
        $this->dossierService->updateDetails($dossier);
    }

    public function updateDecisionDocument(WooDecision $dossier, UploadedFile $uploadedFile): void
    {
        $this->dossierWorkflowManager->applyTransition($dossier, DossierStatusTransition::UPDATE_DECISION_DOCUMENT);

        $this->dossierService->updateDecisionDocument($uploadedFile, $dossier);
    }

    public function updateDecision(WooDecision $dossier): void
    {
        $this->dossierWorkflowManager->applyTransition($dossier, DossierStatusTransition::UPDATE_DECISION);

        $this->dossierService->updateDecision($dossier);
    }

    public function updateInventory(WooDecision $dossier, FormInterface $form): void
    {
        $this->dossierWorkflowManager->applyTransition($dossier, DossierStatusTransition::UPDATE_INVENTORY);

        $uploadedFile = $form->get('inventory')->getData();
        if (! $uploadedFile instanceof UploadedFile) {
            throw new \RuntimeException('Missing inventory uploadfile');
        }

        $this->dossierService->processInventory($uploadedFile, $dossier);
    }

    public function confirmInventoryUpdate(WooDecision $dossier): void
    {
        $this->dossierWorkflowManager->applyTransition($dossier, DossierStatusTransition::UPDATE_INVENTORY);

        $this->dossierService->confirmInventoryUpdate($dossier);
    }

    public function rejectInventoryUpdate(WooDecision $dossier): void
    {
        $this->dossierWorkflowManager->applyTransition($dossier, DossierStatusTransition::UPDATE_INVENTORY);

        $this->dossierService->rejectInventoryUpdate($dossier);
    }

    public function publish(AbstractDossier $dossier): void
    {
        $this->dossierService->updateHistory($dossier);
        $this->dossierService->handleEntityUpdate($dossier);

        if ($this->dossierPublisher->canPublish($dossier)) {
            $this->dossierPublisher->publish($dossier);

            return;
        }

        if ($this->dossierPublisher->canPublishAsPreview($dossier)) {
            $this->dossierPublisher->publishAsPreview($dossier);

            return;
        }

        if ($this->dossierPublisher->canSchedulePublication($dossier)) {
            $this->dossierPublisher->schedulePublication($dossier);
        }
    }

    public function removeInventory(WooDecision $dossier): void
    {
        $this->dossierWorkflowManager->applyTransition($dossier, DossierStatusTransition::UPDATE_INVENTORY);

        $this->inventoryService->removeInventories($dossier);

        $this->dossierService->validateCompletion($dossier);
    }

    public function delete(AbstractDossier $dossier): void
    {
        if (! $this->dossierWorkflowManager->isTransitionAllowed($dossier, DossierStatusTransition::DELETE)) {
            throw DossierWorkflowException::forTransitionNotAllowed($dossier, DossierStatusTransition::DELETE);
        }

        $this->messageBus->dispatch(
            DeleteDossierCommand::forDossier($dossier),
        );
    }

    public function dispatch(object $command): void
    {
        $this->messageBus->dispatch($command);
    }
}
