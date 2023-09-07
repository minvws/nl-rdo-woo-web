<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\DecisionDocument;
use App\Entity\Document;
use App\Entity\Dossier;
use App\Message\IngestDossierMessage;
use App\Message\RemoveDossierMessage;
use App\Message\UpdateDossierMessage;
use App\Service\Inventory\InventoryService;
use App\Service\Inventory\ProcessInventoryResult;
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
 */
class DossierService
{
    public function __construct(
        private readonly EntityManagerInterface $doctrine,
        private readonly InventoryService $inventoryService,
        private readonly MessageBusInterface $messageBus,
        private readonly LoggerInterface $logger,
        private readonly InquiryService $inquiryService,
        private readonly DocumentStorageService $documentStorage,
    ) {
    }

    /**
     * Creates a new dossier with an inventory file and decision document.
     */
    public function create(
        Dossier $dossier,
        ?UploadedFile $inventoryUpload,
        ?UploadedFile $decisionUpload
    ): ProcessInventoryResult {
        $dossier->setStatus(Dossier::STATUS_CONCEPT);

        $this->doctrine->persist($dossier);
        if ($dossier->getId() === null) {
            $this->logger->error('Dossier has an empty ID. This should not happen');

            return new ProcessInventoryResult();
        }

        if ($decisionUpload instanceof UploadedFile) {
            $this->storeDecisionDocument($decisionUpload, $dossier);
        }

        // Wrap in transaction, so we can roll back if inventory processing fails
        $this->doctrine->beginTransaction();
        $this->doctrine->flush();

        $result = $this->inventoryService->processInventory($inventoryUpload, $dossier);
        if ($result->isSuccessful()) {
            // Commit inventory and dossier changes
            $this->doctrine->commit();

            if ($dossier->getId()) {
                $this->messageBus->dispatch(new UpdateDossierMessage($dossier->getId()));
            }

            $this->logger->info('Dossier created', [
                'dossier' => $dossier->getId(),
            ]);
        } else {
            // Rollback inventory and dossier changes
            $this->doctrine->rollback();

            $this->logger->info('Dossier creation failed', [
                'dossier' => $dossier->getId(),
                'errors' => $result->getAllErrors(),
            ]);
        }

        return $result;
    }

    public function update(
        Dossier $dossier,
        ?UploadedFile $inventoryUpload,
        ?UploadedFile $decisionUpload
    ): ProcessInventoryResult {
        if ($decisionUpload instanceof UploadedFile) {
            $this->storeDecisionDocument($decisionUpload, $dossier);
        }

        // Wrap in transaction
        $this->doctrine->beginTransaction();
        $this->doctrine->persist($dossier);
        $this->doctrine->flush();

        if ($dossier->getId() === null) {
            return new ProcessInventoryResult();
        }

        if ($inventoryUpload instanceof UploadedFile) {
            $result = $this->inventoryService->processInventory($inventoryUpload, $dossier);
        } else {
            $result = new ProcessInventoryResult();
        }

        if ($result->isSuccessful()) {
            // Commit inventory and dossier changes
            $this->doctrine->commit();

            $this->messageBus->dispatch(new UpdateDossierMessage($dossier->getId()));

            $this->logger->info('Dossier updated', [
                'dossier' => $dossier->getId(),
            ]);
        } else {
            // Rollback everything mutated in this transaction
            $this->doctrine->rollback();

            $this->logger->info('Dossier update failed', [
                'dossier' => $dossier->getId(),
                'errors' => $result->getAllErrors(),
            ]);
        }

        return $result;
    }

    public function remove(Dossier $dossier): void
    {
        if ($dossier->getId() === null) {
            return;
        }

        // Remove from elasticsearch
        $this->messageBus->dispatch(new RemoveDossierMessage($dossier->getId()));
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

        switch ($newState) {
            case Dossier::STATUS_COMPLETED:
                // Check all documents present
                foreach ($dossier->getDocuments() as $document) {
                    if ($document->shouldBeUploaded() && ! $document->isUploaded()) {
                        $this->logger->error('Invalid state change', [
                            'dossier' => $dossier->getId(),
                            'oldState' => $dossier->getStatus(),
                            'newState' => $newState,
                            'reason' => 'Not all documents uploaded',
                        ]);

                        throw new \InvalidArgumentException('Not all documents are uploaded in this dossier');
                    }
                }

                if ($dossier->getDecisionDocument()?->getFileInfo()->isUploaded() !== true) {
                    throw new \InvalidArgumentException('Decision document is missing');
                }

                break;
        }

        // Set new status
        $dossier->setStatus($newState);
        $this->doctrine->flush();

        $this->messageBus->dispatch(new UpdateDossierMessage($dossier->getId()));

        $this->logger->info('Dossier state changed', [
            'dossier' => $dossier->getId(),
            'oldState' => $dossier->getStatus(),
            'newState' => $newState,
        ]);
    }

    /**
     * Store the decision document to disk and add it to the dossier.
     */
    protected function storeDecisionDocument(UploadedFile $upload, Dossier $dossier): void
    {
        if ($dossier->getId() === null) {
            return;
        }

        $decisionDocument = $dossier->getDecisionDocument();
        if (! $decisionDocument) {
            // Create inventory if not exists yet
            $decisionDocument = new DecisionDocument();
            $dossier->setDecisionDocument($decisionDocument);
            $decisionDocument->setDossier($dossier);
        }

        $file = $decisionDocument->getFileInfo();
        $file->setSourceType(SourceType::SOURCE_PDF);
        $file->setType('pdf');

        // Set original filename
        $filename = 'decision-' . $dossier->getDossierNr() . '.' . $upload->getClientOriginalExtension();
        $file->setName($filename);

        $this->doctrine->persist($decisionDocument);

        if (! $this->documentStorage->storeDocument($upload, $decisionDocument)) {
            throw new \RuntimeException('Could not store decision document');
        }
    }

    public function dispatchIngest(Dossier $dossier): void
    {
        if ($dossier->getId() === null) {
            return;
        }

        $message = new IngestDossierMessage($dossier->getId());
        $this->messageBus->dispatch($message);
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

        $inquiryIds = $this->inquiryService->getInquiries();

        // Check if any inquiry id from the dossier is in the session inquiry ids.
        foreach ($dossier->getInquiries() as $inquiry) {
            if (in_array($inquiry->getId(), $inquiryIds)) {
                // Inquiry id is set in the session, so allow viewing
                return true;
            }
        }

        // If document is not visible, and no document is given, deny viewing
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
}
