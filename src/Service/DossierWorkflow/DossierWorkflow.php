<?php

declare(strict_types=1);

namespace App\Service\DossierWorkflow;

use App\Entity\Dossier;
use App\Message\IngestDecisionMessage;
use App\Message\RemoveInventoryAndDocumentsMessage;
use App\Message\UpdateDossierArchivesMessage;
use App\Message\UpdateDossierMessage;
use App\Service\DossierService;
use App\Service\Inventory\ProcessInventoryResult;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * This class implements all actions that can be applied to a dossier as part of the create/update/delete workflow.
 *
 * @SuppressWarnings(CouplingBetweenObjects)
 */
class DossierWorkflow
{
    public function __construct(
        private readonly EntityManagerInterface $doctrine,
        private readonly DossierService $dossierService,
        private readonly WorkflowStatusFactory $statusFactory,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function getStatus(Dossier $dossier, StepName $currentStep = null): DossierWorkflowStatus
    {
        return $this->statusFactory->getWorkflowStatus($dossier, $currentStep);
    }

    public function create(Dossier $dossier): void
    {
        $dossier->setStatus(Dossier::STATUS_CONCEPT); // TODO set default state in dossier constructor
        $dossier->setDecision(''); // TODO make nullable, this will not be set before step 2
        $dossier->setSummary(''); // TODO make nullable, this will not be set before step 2

        $this->updateDetails($dossier);
    }

    public function updateDetails(Dossier $dossier): void
    {
        $this->doctrine->persist($dossier);
        $this->doctrine->flush();

        $this->messageBus->dispatch(
            UpdateDossierMessage::forDossier($dossier)
        );
    }

    public function updateDecision(Dossier $dossier, FormInterface $form): void
    {
        if (! $this->getStatus($dossier)->isReadyForDecision()) {
            throw new \RuntimeException('Cannot update decision for this dossier');
        }

        /** @var ?UploadedFile $uploadedFile */
        $uploadedFile = $form->get('decision_document')->getData();
        if ($uploadedFile instanceof UploadedFile) {
            $this->dossierService->removeDecisionDocument($dossier);
            $this->dossierService->storeDecisionDocument($uploadedFile, $dossier);
            $this->messageBus->dispatch(
                IngestDecisionMessage::forDossier($dossier)
            );
        }

        $this->doctrine->persist($dossier);
        $this->doctrine->flush();

        // If the new dossier decision value no longer needs inventory and documents: remove them
        if (! $dossier->needsInventoryAndDocuments()) {
            $this->messageBus->dispatch(
                RemoveInventoryAndDocumentsMessage::forDossier($dossier)
            );
        }
    }

    public function updateInventory(Dossier $dossier, FormInterface $form): ProcessInventoryResult
    {
        if (! $this->getStatus($dossier)->isReadyForDocuments()) {
            throw new \RuntimeException('Cannot update inventory for this dossier');
        }

        $uploadedFile = $form->get('inventory')->getData();
        if (! $uploadedFile instanceof UploadedFile) {
            throw new \RuntimeException('Missing inventory uploadfile');
        }

        $oldInventory = $dossier->getInventory();
        $oldRawInventory = $dossier->getRawInventory();

        $result = $this->dossierService->processInventory($uploadedFile, $dossier);

        if ($result->isSuccessful()) {
            if ($oldInventory) {
                $this->dossierService->removeInventory($oldInventory, $dossier, false);
            }
            if ($oldRawInventory) {
                $this->dossierService->removeRawInventory($oldRawInventory, $dossier, false);
            }
            $this->messageBus->dispatch(
                UpdateDossierArchivesMessage::forDossier($dossier)
            );
        }

        foreach ($result->getGenericErrors() as $errorMessage) {
            $form->addError(new FormError($errorMessage));
        }

        foreach ($result->getRowErrors() as $lineNum => $lineErrors) {
            foreach ($lineErrors as $error) {
                $form->addError(new FormError(sprintf('Line %d: %s', $lineNum, $error)));
            }
        }

        return $result;
    }

    public function publish(Dossier $dossier, bool $asPreview = false): void
    {
        if ($dossier->getId() === null || ! $this->getStatus($dossier)->isReadyForPublication()) {
            throw new \RuntimeException('Cannot publish this dossier');
        }

        $publicationStatus = $asPreview ? Dossier::STATUS_PREVIEW : Dossier::STATUS_PUBLISHED;

        $workflowStatus = $this->getStatus($dossier);
        if (! in_array($publicationStatus, $workflowStatus->getAllowedStatusUpdates())) {
            throw new \RuntimeException('Publication status not allowed');
        }

        $dossier->setStatus($publicationStatus);
        $this->doctrine->flush();

        $this->messageBus->dispatch(
            new UpdateDossierMessage($dossier->getId())
        );

        $this->messageBus->dispatch(
            UpdateDossierArchivesMessage::forDossier($dossier)
        );
    }

    public function removeInventory(Dossier $dossier): void
    {
        if (! $this->getStatus($dossier)->isReadyForDocuments()) {
            throw new \RuntimeException('Cannot remove inventory for this dossier');
        }

        $this->dossierService->removeInventories($dossier);
    }
}
