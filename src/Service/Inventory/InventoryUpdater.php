<?php

declare(strict_types=1);

namespace App\Service\Inventory;

use App\Domain\Publication\Dossier\Type\WooDecision\Event\DocumentUpdateEvent;
use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReportDispatcher;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Search\SearchDispatcher;
use App\Entity\Document;
use App\Exception\InventoryUpdaterException;
use App\Repository\DocumentRepository;
use App\Service\DossierService;
use App\Service\Inquiry\InquiryService;
use App\Service\Inventory\Progress\RunProgress;
use App\Service\Inventory\Reader\InventoryReaderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
readonly class InventoryUpdater
{
    public function __construct(
        private EntityManagerInterface $doctrine,
        private DocumentUpdater $documentUpdater,
        private DocumentComparator $documentComparator,
        private DocumentRepository $documentRepository,
        private DossierService $dossierService,
        private InquiryService $inquiryService,
        private MessageBusInterface $messageBus,
        private SearchDispatcher $searchDispatcher,
        private ProductionReportDispatcher $dispatcher,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function applyChangesetToDatabase(
        WooDecision $dossier,
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
            $documentChangeStatus = $changeset->getStatus($documentNr);
            if ($documentChangeStatus === InventoryChangeset::UNCHANGED) {
                continue;
            }

            $document = $this->documentRepository->findByDocumentNumber($documentNr);
            if ($documentChangeStatus === InventoryChangeset::ADDED && $document === null) {
                $document = new Document();
                $document->setDocumentNr($documentNr->getValue());

                $this->documentUpdater->databaseUpdate($documentMetadata, $dossier, $document);
                $inquiryChangeset->updateCaseNrsForDocument($document, $documentMetadata->getCaseNumbers());

                $documentsToUpdate[] = $document;

                if (count($documentMetadata->getRefersTo()) !== 0) {
                    $docReferralUpdates[$documentNr->getValue()] = $documentMetadata->getRefersTo();
                }

                continue;
            }

            if ($documentChangeStatus === InventoryChangeset::UPDATED && $document instanceof Document) {
                $this->messageBus->dispatch(
                    new DocumentUpdateEvent($dossier, $documentMetadata, $document)
                );

                $this->documentUpdater->databaseUpdate($documentMetadata, $dossier, $document);
                $inquiryChangeset->updateCaseNrsForDocument($document, $documentMetadata->getCaseNumbers());

                $documentsToUpdate[] = $document;

                if ($this->documentComparator->hasRefersToUpdate($dossier, $document, $documentMetadata)) {
                    $docReferralUpdates[$document->getDocumentNr()] = $documentMetadata->getRefersTo();
                }

                continue;
            }

            throw InventoryUpdaterException::forStateMismatch();
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

    private function applyDeletes(InventoryChangeset $changeset, WooDecision $dossier): void
    {
        foreach ($changeset->getDeleted() as $documentNr) {
            $document = $this->getDocument($documentNr);
            if (! $document instanceof Document || ! $dossier->getStatus()->isConcept()) {
                throw InventoryUpdaterException::forStateMismatch();
            }

            // Remove the dossier-document relationship immediately, if needed the document and related files removed asynchronously
            $document->getDossiers()->removeElement($dossier);
            $this->doctrine->persist($document);
        }

        $this->doctrine->flush();
    }

    public function sendMessagesForChangeset(InventoryChangeset $changeset, WooDecision $dossier, RunProgress $runProgress): void
    {
        $this->dispatcher->dispatchGenerateInventoryCommand($dossier->getId());
        $this->dossierService->generateArchives($dossier);
        $this->searchDispatcher->dispatchIndexDossierCommand($dossier->getId());

        foreach ($changeset->getAll() as $documentNr => $action) {
            $runProgress->tick();

            if ($action === InventoryChangeset::UNCHANGED) {
                continue;
            }

            $document = $this->getDocument($documentNr);
            if (! $document instanceof Document) {
                throw InventoryUpdaterException::forStateMismatch();
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
    private function applyDocumentReferralUpdates(WooDecision $dossier, array $docReferralUpdates): void
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
