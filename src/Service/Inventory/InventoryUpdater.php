<?php

declare(strict_types=1);

namespace App\Service\Inventory;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Message\GenerateSanitizedInventoryMessage;
use App\Message\UpdateDossierArchivesMessage;
use App\Message\UpdateDossierMessage;
use App\Repository\DocumentRepository;
use App\Service\HistoryService;
use App\Service\Inventory\Progress\RunProgress;
use App\Service\Inventory\Reader\InventoryReaderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 */
class InventoryUpdater
{
    public function __construct(
        private readonly EntityManagerInterface $doctrine,
        private readonly DocumentUpdater $documentUpdater,
        private readonly DocumentRepository $documentRepository,
        private readonly MessageBusInterface $messageBus,
        private readonly HistoryService $historyService,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function applyChangesetToDatabase(
        Dossier $dossier,
        InventoryReaderInterface $reader,
        InventoryChangeset $changeset,
        RunProgress $runProgress,
    ): void {
        $documentGenerator = $reader->getDocumentMetadataGenerator($dossier);

        $inquiryUpdater = new InquiryUpdater($dossier->getOrganisation(), $this->messageBus);

        $documentsToUpdate = [];
        $currentProgress = $runProgress->getCurrentCount();
        foreach ($documentGenerator as $inventoryItem) {
            if (count($documentsToUpdate) > 1000) {
                $this->doctrine->flush();
                foreach ($documentsToUpdate as $doc) {
                    $this->doctrine->detach($doc);
                }
                $documentsToUpdate = [];
            }

            $rowIndex = $inventoryItem->getIndex();
            $runProgress->update($currentProgress + $rowIndex);

            $documentMetadata = $inventoryItem->getDocumentMetadata();
            if (! $documentMetadata instanceof DocumentMetadata) {
                continue;
            }

            $documentNr = DocumentNumber::fromDossierAndDocumentMetadata($dossier, $documentMetadata);
            $document = $this->getDocument($documentNr->getValue());

            $action = $changeset->getAction($documentNr);

            if ($action === null) {
                continue;
            }

            if ($action === InventoryChangeset::ACTION_CREATE && $document === null) {
                $document = new Document();

                $document->setDocumentNr($documentNr->getValue());
                $this->doctrine->persist($document); // For getting ID

                $this->documentUpdater->databaseUpdate($documentMetadata, $dossier, $document);
                $inquiryUpdater->addToChangeset($documentMetadata, $document);

                $documentsToUpdate[] = $document;

                continue;
            }

            // Get diffs
            $changes = [];
            $this->doctrine->getUnitOfWork()->computeChangeSets();
            if ($document) {
                foreach ($this->doctrine->getUnitOfWork()->getEntityChangeSet($document) as $key => $entry) {
                    /** @var array<string, mixed> $entry */
                    $data = $entry[$key];
                    /** @var array<int, string> $data */
                    $changes[$key] = $data[1] ?? '';
                }
            }

            if ($action === InventoryChangeset::ACTION_UPDATE && $document instanceof Document) {
                if ($documentMetadata->getJudgement() != $document->getJudgement()) {
                    $this->historyService->addDocumentEntry($document, 'document_judgement_' . $documentMetadata->getJudgement()->value, [
                        'old' => '%' . ($document->getJudgement()->value ?? '') . '%',
                        'new' => '%' . $documentMetadata->getJudgement()->value . '%',
                    ], flush: false);
                }

                if ($changes) {
                    // All changes are translated
                    foreach ($changes as $key => $value) {
                        $changes['%' . $key . '%'] = $value;
                    }

                    $this->historyService->addDocumentEntry($document, 'document_inventory_updated', [
                        'changes' => $changes,
                    ], flush: false);
                }

                $this->documentUpdater->databaseUpdate($documentMetadata, $dossier, $document);
                $inquiryUpdater->addToChangeset($documentMetadata, $document);

                $documentsToUpdate[] = $document;

                continue;
            }

            throw new \RuntimeException('State mismatch between database and changeset');
        }

        $this->doctrine->flush();
        foreach ($documentsToUpdate as $doc) {
            $this->doctrine->detach($doc);
        }
        unset($documentsToUpdate);

        $inquiryUpdater->flushChangeset();

        $this->applyDeletes($changeset, $dossier);
    }

    private function applyDeletes(InventoryChangeset $changeset, Dossier $dossier): void
    {
        foreach ($changeset->getDeletes() as $documentNr) {
            $document = $this->getDocument($documentNr);
            if (! $document instanceof Document || $dossier->getStatus() !== Dossier::STATUS_CONCEPT) {
                throw new \RuntimeException('State mismatch between database and changeset');
            }

            // Remove the dossier-document relationship immediately, if needed the document and related files removed asynchronously
            $document->getDossiers()->removeElement($dossier);
            $this->doctrine->persist($document);
        }

        $this->doctrine->flush();
    }

    public function sendMessagesForChangeset(InventoryChangeset $changeset, Dossier $dossier, RunProgress $runProgress): void
    {
        $this->messageBus->dispatch(GenerateSanitizedInventoryMessage::forDossier($dossier));

        if (! $dossier->isConcept()) {
            $this->messageBus->dispatch(UpdateDossierArchivesMessage::forDossier($dossier));
            $this->messageBus->dispatch(UpdateDossierMessage::forDossier($dossier));
        }

        foreach ($changeset->getAll() as $documentNr => $action) {
            $runProgress->tick();
            $document = $this->getDocument($documentNr);

            if (! $document instanceof Document) {
                throw new \RuntimeException('State mismatch between database and changeset');
            }

            if ($action === InventoryChangeset::ACTION_DELETE) {
                $this->documentUpdater->asyncRemove($document, $dossier);
                continue;
            }

            if (! $dossier->isConcept()) {
                $this->documentUpdater->asyncUpdate($document);
            }

            $this->doctrine->detach($document);
        }
    }

    private function getDocument(int|string $documentNr): ?Document
    {
        return $this->documentRepository->findOneBy(['documentNr' => $documentNr]);
    }
}
