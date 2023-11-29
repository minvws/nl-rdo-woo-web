<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\DecisionDocument;
use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\InventoryProcessRun;
use App\Entity\WithdrawReason;
use App\Exception\ProcessInventoryException;
use App\Message\GenerateSanitizedInventoryMessage;
use App\Message\IngestDecisionMessage;
use App\Message\IngestDossierMessage;
use App\Message\InventoryProcessRunMessage;
use App\Message\RemoveDossierMessage;
use App\Message\RemoveInventoryAndDocumentsMessage;
use App\Message\UpdateDossierArchivesMessage;
use App\Message\UpdateDossierMessage;
use App\Service\DocumentWorkflow\DocumentWorkflowStatus;
use App\Service\DossierWorkflow\StepName;
use App\Service\DossierWorkflow\WorkflowStatusFactory;
use App\Service\Inquiry\InquiryService;
use App\Service\Inquiry\InquirySessionService;
use App\Service\Storage\DocumentStorageService;
use App\SourceType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * This class handles dossier management.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class DossierService
{
    public function __construct(
        private readonly EntityManagerInterface $doctrine,
        private readonly MessageBusInterface $messageBus,
        private readonly LoggerInterface $logger,
        private readonly InquirySessionService $inquirySession,
        private readonly DocumentStorageService $documentStorage,
        private readonly WorkflowStatusFactory $statusFactory,
        private readonly DocumentService $documentService,
        private readonly InquiryService $inquiryService,
    ) {
    }

    public function updateDecision(Dossier $dossier): void
    {
        $this->persist($dossier);

        // If the dossier no longer needs inventory and documents: remove them
        if (! $dossier->needsInventoryAndDocuments()) {
            $this->messageBus->dispatch(
                RemoveInventoryAndDocumentsMessage::forDossier($dossier)
            );
        }
    }

    public function remove(Dossier $dossier): void
    {
        if ($dossier->getId() === null) {
            return;
        }

        $this->messageBus->dispatch(RemoveDossierMessage::forDossier($dossier));
    }

    public function ingest(Dossier $dossier): void
    {
        if ($dossier->getId() === null) {
            return;
        }

        $this->messageBus->dispatch(new IngestDossierMessage($dossier->getId()));
    }

    public function update(Dossier $dossier): void
    {
        if ($dossier->getId() === null) {
            return;
        }

        $this->messageBus->dispatch(UpdateDossierMessage::forDossier($dossier));
    }

    public function generateSanitizedInventory(Dossier $dossier): void
    {
        if ($dossier->getId() === null) {
            return;
        }

        $this->messageBus->dispatch(GenerateSanitizedInventoryMessage::forDossier($dossier));
    }

    public function generateArchives(Dossier $dossier): void
    {
        if ($dossier->getId() === null) {
            return;
        }

        $this->messageBus->dispatch(UpdateDossierArchivesMessage::forDossier($dossier));
    }

    public function changeState(Dossier $dossier, string $newState): void
    {
        if ($dossier->getId() === null) {
            return;
        }

        if (! $dossier->isAllowedState($newState)) {
            $this->logger->error('Invalid state change', [
                'dossier' => $dossier->getId(),
                'oldState' => $dossier->getStatus(),
                'newState' => $newState,
                'reason' => 'Invalid state',
            ]);

            throw new \InvalidArgumentException('Invalid state change');
        }

        // Set new status
        $dossier->setStatus($newState);
        $this->persist($dossier);

        $this->messageBus->dispatch(UpdateDossierArchivesMessage::forDossier($dossier));

        $this->logger->info('Dossier state changed', [
            'dossier' => $dossier->getId(),
            'oldState' => $dossier->getStatus(),
            'newState' => $newState,
        ]);
    }

    /**
     * Store the decision document to disk and add it to the dossier.
     */
    public function updateDecisionDocument(UploadedFile $upload, Dossier $dossier): void
    {
        if ($dossier->getId() === null) {
            return;
        }

        $this->logger->info('uploaded decision file', [
            'path' => $upload->getRealPath(),
            'original_file' => $upload->getClientOriginalName(),
            'size' => $upload->getSize(),
            'file_hash' => hash_file('sha256', $upload->getRealPath()),
        ]);

        $decisionDocument = $dossier->getDecisionDocument();
        if (! $decisionDocument) {
            $decisionDocument = new DecisionDocument();
            $dossier->setDecisionDocument($decisionDocument);
            $decisionDocument->setDossier($dossier);
        }

        $fileInfo = $decisionDocument->getFileInfo();

        // If there was already a decision file: clean it up
        if ($fileInfo->isUploaded()) {
            $this->documentStorage->removeFileForEntity($decisionDocument);
            $fileInfo->removeFileProperties();
        }

        $fileInfo->setSourceType(SourceType::SOURCE_PDF);
        $fileInfo->setType('pdf');
        $fileInfo->setName($upload->getClientOriginalName());

        $this->doctrine->persist($decisionDocument);

        if (! $this->documentStorage->storeDocument($upload, $decisionDocument)) {
            throw new \RuntimeException('Could not store decision document');
        }

        $this->validateCompletion($dossier);

        $this->messageBus->dispatch(
            IngestDecisionMessage::forDossier($dossier)
        );
    }

    // Returns true when the dossier (and/or document) is allowed to be viewed. This will also
    // consider documents and dossiers which are marked as preview and that are allowed by the session.
    public function isViewingAllowed(Dossier $dossier, Document $document = null): bool
    {
        // If dossier is published, allow viewing
        if ($dossier->getStatus() == Dossier::STATUS_PUBLISHED) {
            return true;
        }

        // If dossier is not preview, deny access
        if ($dossier->getStatus() != Dossier::STATUS_PREVIEW) {
            return false;
        }

        $inquiryIds = $this->inquirySession->getInquiries();

        // Check if any inquiry id from the dossier is in the session inquiry ids.
        foreach ($dossier->getInquiries() as $inquiry) {
            if (in_array($inquiry->getId(), $inquiryIds)) {
                // Inquiry id is set in the session, so allow viewing
                return true;
            }
        }

        // If dossier is not visible, and no document is given, deny viewing
        if (! $document) {
            return false;
        }

        // Check all inquiry ids from the document to see if we have one matching in our session.
        foreach ($document->getInquiries() as $inquiry) {
            if (in_array($inquiry->getId(), $inquiryIds)) {
                // Inquiry id is set in the session, so allow viewing
                return true;
            }
        }

        return false;
    }

    public function processInventory(UploadedFile $upload, Dossier $dossier): InventoryProcessRun
    {
        // First cleanup any old process run
        $processRun = $dossier->getProcessRun();
        if ($processRun) {
            if ($processRun->isNotFinal()) {
                throw new \RuntimeException();
            }

            $this->documentStorage->removeFileForEntity($processRun);
            $this->doctrine->remove($processRun);
            $this->doctrine->flush();
        }

        // Now create the new run
        $run = new InventoryProcessRun($dossier);

        $file = $run->getFileInfo();
        $file->setSourceType(SourceType::SOURCE_SPREADSHEET);
        $file->setType('pdf');
        $file->setName($upload->getClientOriginalName());

        $this->doctrine->persist($run);
        $this->doctrine->flush();

        if (! $this->documentStorage->storeDocument($upload, $run)) {
            $this->logger->error('Could not store the inventory spreadsheet.', [
                'dossier' => $dossier->getId()?->toRfc4122() ?? 'unknown',
                'filename' => $upload->getClientOriginalName(),
            ]);

            $run->addGenericException(ProcessInventoryException::forInventoryCannotBeStored());
            $run->fail();

            $this->doctrine->persist($run);
            $this->doctrine->flush();

            throw new \RuntimeException('Could not store the inventory upload');
        }

        $this->messageBus->dispatch(
            new InventoryProcessRunMessage($run->getId())
        );

        return $run;
    }

    public function confirmInventoryUpdate(Dossier $dossier): void
    {
        $run = $dossier->getProcessRun();
        if (! $run) {
            throw new \RuntimeException('There is no run to confirm for this dossier');
        }

        $run->confirm();

        $this->doctrine->persist($run);
        $this->doctrine->flush();

        $this->messageBus->dispatch(
            new InventoryProcessRunMessage($run->getId())
        );
    }

    public function rejectInventoryUpdate(Dossier $dossier): void
    {
        $run = $dossier->getProcessRun();
        if (! $run) {
            throw new \RuntimeException('There is no run to reject for this dossier');
        }

        $run->reject();

        $this->doctrine->persist($run);
        $this->doctrine->flush();
    }

    /**
     * Validate dossier completion and set dossier completed flag.
     */
    public function validateCompletion(Dossier $dossier, bool $flush = true): bool
    {
        $completed = $this->statusFactory->getWorkflowStatus($dossier, StepName::DETAILS)->isCompleted();

        $dossier->setCompleted($completed);
        $this->doctrine->persist($dossier);

        if ($flush) {
            $this->doctrine->flush();
        }

        return $completed;
    }

    public function updatePublication(Dossier $dossier): void
    {
        $now = new \DateTimeImmutable();

        if (
            $dossier->getPublicationDate() <= $now
            && in_array($dossier->getStatus(), [Dossier::STATUS_CONCEPT, Dossier::STATUS_SCHEDULED, Dossier::STATUS_PREVIEW], true)
        ) {
            $this->changeState($dossier, Dossier::STATUS_PUBLISHED);
            $this->handlePublication($dossier);

            return;
        }

        if ($dossier->getPreviewDate() <= $now && in_array($dossier->getStatus(), [Dossier::STATUS_SCHEDULED, Dossier::STATUS_CONCEPT], true)) {
            $this->changeState($dossier, Dossier::STATUS_PREVIEW);
            $this->handlePublication($dossier);

            return;
        }

        if ($dossier->getStatus() === Dossier::STATUS_CONCEPT) {
            $this->changeState($dossier, Dossier::STATUS_SCHEDULED);

            return;
        }

        // If there is no status change still persist the dossier, as the planned dates might have changed.
        $this->doctrine->persist($dossier);
        $this->doctrine->flush();
    }

    public function updateDetails(Dossier $dossier): void
    {
        $this->persist($dossier);
    }

    private function persist(Dossier $dossier): void
    {
        $this->validateCompletion($dossier);

        $this->messageBus->dispatch(
            UpdateDossierMessage::forDossier($dossier)
        );
    }

    public function withdrawAllDocuments(Dossier $dossier, WithdrawReason $reason, string $explanation): void
    {
        foreach ($dossier->getDocuments() as $document) {
            $documentStatus = new DocumentWorkflowStatus($document);
            if ($documentStatus->canWithdraw()) {
                $this->documentService->withdraw($document, $reason, $explanation);
            }
        }
    }

    private function handlePublication(Dossier $dossier): void
    {
        foreach ($dossier->getInquiries() as $inquiry) {
            $this->inquiryService->generateInventory($inquiry);
            $this->inquiryService->generateArchives($inquiry);
        }
    }
}
