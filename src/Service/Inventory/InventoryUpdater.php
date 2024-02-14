<?php

declare(strict_types=1);

namespace App\Service\Inventory;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Repository\DocumentRepository;
use App\Service\DossierService;
use App\Service\HistoryService;
use App\Service\Inquiry\InquiryService;
use App\Service\Inventory\Progress\RunProgress;
use App\Service\Inventory\Reader\InventoryReaderInterface;
use Doctrine\ORM\EntityManagerInterface;

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
        private readonly DocumentComparator $documentComparator,
        private readonly DocumentRepository $documentRepository,
        private readonly DossierService $dossierService,
        private readonly HistoryService $historyService,
        private readonly InquiryService $inquiryService,
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

        $inquiryChangeset = new InquiryChangeset($dossier->getOrganisation());

        $documentsToUpdate = [];
        $docReferralUpdates = [];
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
            $document = $this->documentRepository->findByDocumentNumber($documentNr);

            $documentChangeStatus = $changeset->getStatus($documentNr);
            if ($documentChangeStatus === InventoryChangeset::UNCHANGED) {
                continue;
            }

            if ($documentChangeStatus === InventoryChangeset::ADDED && $document === null) {
                $document = new Document();

                $document->setDocumentNr($documentNr->getValue());
                $this->doctrine->persist($document); // For getting ID

                $this->documentUpdater->databaseUpdate($documentMetadata, $dossier, $document);
                $inquiryChangeset->updateCaseNrsForDocument($document, $documentMetadata->getCaseNumbers());

                $documentsToUpdate[] = $document;

                if (count($documentMetadata->getRefersTo()) !== 0) {
                    $docReferralUpdates[$documentNr->getValue()] = $documentMetadata->getRefersTo();
                }

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

            if ($documentChangeStatus === InventoryChangeset::UPDATED && $document instanceof Document) {
                if ($documentMetadata->getJudgement() != $document->getJudgement()) {
                    $this->historyService->addDocumentEntry($document, 'document_judgement_' . $documentMetadata->getJudgement()->value, [
                        'old' => '%' . ($document->getJudgement()->value ?? '') . '%',
                        'new' => '%' . $documentMetadata->getJudgement()->value . '%',
                    ], flush: false);
                }

                if ($changes) {
                    // All changes are translated
                    foreach ($changes as $key => $value) {
                        $changes[$key] = '%' . $value . '%';
                    }

                    $this->historyService->addDocumentEntry($document, 'document_inventory_updated', [
                        'changes' => $changes,
                    ], flush: false);
                }

                $this->documentUpdater->databaseUpdate($documentMetadata, $dossier, $document);
                $inquiryChangeset->updateCaseNrsForDocument($document, $documentMetadata->getCaseNumbers());

                $documentsToUpdate[] = $document;

                if ($this->documentComparator->hasRefersToUpdate($dossier, $document, $documentMetadata)) {
                    $docReferralUpdates[$document->getDocumentNr()] = $documentMetadata->getRefersTo();
                }

                continue;
            }

            throw new \RuntimeException('State mismatch between database and changeset');
        }

        $this->doctrine->flush();
        foreach ($documentsToUpdate as $doc) {
            $this->doctrine->detach($doc);
        }
        unset($documentsToUpdate);

        // These updates must be applied outside the main document process loop, as referred docs might not exist yet.
        $this->applyDocumentReferralUpdates($dossier, $docReferralUpdates);

        $this->inquiryService->applyChangesetAsync($inquiryChangeset);

        $this->applyDeletes($changeset, $dossier);
    }

    private function applyDeletes(InventoryChangeset $changeset, Dossier $dossier): void
    {
        foreach ($changeset->getDeleted() as $documentNr) {
            $document = $this->getDocument($documentNr);
            if (! $document instanceof Document || ! $dossier->getStatus()->isConcept()) {
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
        $this->dossierService->generateSanitizedInventory($dossier);
        $this->dossierService->generateArchives($dossier);
        $this->dossierService->update($dossier);

        foreach ($changeset->getAll() as $documentNr => $action) {
            $runProgress->tick();

            if ($action === InventoryChangeset::UNCHANGED) {
                continue;
            }

            $document = $this->getDocument($documentNr);
            if (! $document instanceof Document) {
                throw new \RuntimeException('State mismatch between database and changeset');
            }

            if ($action === InventoryChangeset::DELETED) {
                $this->documentUpdater->asyncRemove($document, $dossier);
                $this->doctrine->detach($document);
                continue;
            }

            if ($dossier->getStatus()->isConcept() && $document->shouldBeUploaded()) {
                // As an optimization skip indexing for concept docs, as these will be indexed during the ingest process
                $this->doctrine->detach($document);
                continue;
            }

            $this->documentUpdater->asyncUpdate($document);
            $this->doctrine->detach($document);
        }
    }

    private function getDocument(int|string $documentNr): ?Document
    {
        return $this->documentRepository->findOneBy(['documentNr' => $documentNr]);
    }

    /**
     * @param array<string, string[]> $docReferralUpdates
     */
    private function applyDocumentReferralUpdates(Dossier $dossier, array $docReferralUpdates): void
    {
        $documentsToUpdate = [];
        foreach ($docReferralUpdates as $documentNr => $refersTo) {
            $document = $this->getDocument($documentNr);
            if (! $document instanceof Document) {
                throw new \RuntimeException('State mismatch between database and document referral updates');
            }

            $this->documentUpdater->updateDocumentReferrals($dossier, $document, $refersTo);

            $documentsToUpdate[] = $document;
            if (count($documentsToUpdate) > 1000) {
                $this->doctrine->flush();
                foreach ($documentsToUpdate as $doc) {
                    $this->doctrine->detach($doc);
                }
                $documentsToUpdate = [];
            }
        }

        $this->doctrine->flush();
        foreach ($documentsToUpdate as $doc) {
            $this->doctrine->detach($doc);
        }
    }
}
