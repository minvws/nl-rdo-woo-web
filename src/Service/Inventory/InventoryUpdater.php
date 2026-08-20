<?php

declare(strict_types=1);

namespace Shared\Service\Inventory;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use RuntimeException;
use Shared\Domain\Publication\BatchDownload\BatchDownloadScope;
use Shared\Domain\Publication\BatchDownload\BatchDownloadService;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Event\DocumentUpdateEvent;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportDispatcher;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportProcessRun;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionDispatcher;
use Shared\Domain\Search\SearchDispatcher;
use Shared\Exception\ProcessInventoryException;
use Shared\Exception\ProductionReportUpdaterException;
use Shared\Exception\TranslatableException;
use Shared\Service\Inquiry\DocumentInquiryNumbers;
use Shared\Service\Inquiry\InquiryChangeset;
use Shared\Service\Inquiry\InquiryService;
use Shared\Service\Inventory\Progress\RunProgress;
use Shared\Service\Inventory\Reader\InventoryReaderInterface;
use Shared\Service\Inventory\Reader\InventoryReadItem;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

use function count;

readonly class InventoryUpdater
{
    public function __construct(
        private EntityManagerInterface $doctrine,
        private DocumentUpdater $documentUpdater,
        private DocumentComparator $documentComparator,
        private DocumentRepository $documentRepository,
        private InquiryService $inquiryService,
        private MessageBusInterface $messageBus,
        private SearchDispatcher $searchDispatcher,
        private ProductionReportDispatcher $dispatcher,
        private BatchDownloadService $batchDownloadService,
        private WooDecisionDispatcher $wooDecisionDispatcher,
    ) {
    }

    /**
     * @throws Exception
     * @throws ExceptionInterface
     */
    public function applyChangesetToDatabase(
        ProductionReportProcessRun $run,
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
                $this->flushAndDetach($documentsToUpdate);
                $documentsToUpdate = [];
            }

            $rowIndex = $inventoryItem->getIndex();
            $runProgress->update($currentProgress + $rowIndex);

            try {
                $document = $this->processInventoryItem($inventoryItem, $dossier, $changeset, $inquiryChangeset, $docReferralUpdates);
                if ($document instanceof Document) {
                    $documentsToUpdate[] = $document;
                }
            } catch (Exception $exception) {
                $this->addRowException($rowIndex, $run, $exception);

                throw $exception;
            }
        }

        $this->flushAndDetach($documentsToUpdate);
        unset($documentsToUpdate);

        // These updates must be applied outside the main document process loop, as referred docs might not exist yet.
        $this->applyDocumentReferralUpdates($dossier, $docReferralUpdates);

        $this->inquiryService->applyChangesetAsync($inquiryChangeset);

        $this->applyDeletes($changeset, $dossier);
    }

    /**
     * @param array<string, array<array-key, string>> $docReferralUpdates
     *
     * @throws Exception
     * @throws ExceptionInterface
     */
    private function processInventoryItem(
        InventoryReadItem $inventoryItem,
        WooDecision $dossier,
        InventoryChangeset $changeset,
        InquiryChangeset $inquiryChangeset,
        array &$docReferralUpdates,
    ): ?Document {
        $documentMetadata = $inventoryItem->getDocumentMetadata();
        if (! $documentMetadata instanceof DocumentMetadata) {
            return null;
        }

        $documentNumber = DocumentNumber::fromPublicationContextAndDossierId(
            $documentMetadata->getPublicationContext(),
            $documentMetadata->getId(),
        );
        $documentChangeStatus = $changeset->getStatus($documentNumber);
        if ($documentChangeStatus === InventoryChangeset::UNCHANGED) {
            return null;
        }

        $document = $this->documentRepository->findOneByDocumentNumberCaseInsensitive($documentNumber->toString());
        if ($documentChangeStatus === InventoryChangeset::ADDED && $document === null) {
            $document = new Document();
            $document->setDocumentNumber($documentNumber->toString());

            $this->applyDocumentUpdate($documentMetadata, $dossier, $document, $inquiryChangeset);

            if (count($documentMetadata->getRefersTo()) !== 0) {
                $docReferralUpdates[$documentNumber->toString()] = $documentMetadata->getRefersTo();
            }

            return $document;
        }

        if ($documentChangeStatus === InventoryChangeset::UPDATED && $document instanceof Document) {
            $this->messageBus->dispatch(
                new DocumentUpdateEvent($dossier, $documentMetadata, $document),
            );

            $this->applyDocumentUpdate($documentMetadata, $dossier, $document, $inquiryChangeset);

            if ($this->documentComparator->hasRefersToUpdate($dossier, $document, $documentMetadata)) {
                $docReferralUpdates[$document->getDocumentNumber()] = $documentMetadata->getRefersTo();
            }

            return $document;
        }

        throw ProductionReportUpdaterException::forStateMismatch();
    }

    /**
     * @throws Exception
     */
    private function applyDocumentUpdate(
        DocumentMetadata $documentMetadata,
        WooDecision $dossier,
        Document $document,
        InquiryChangeset $inquiryChangeset,
    ): void {
        $this->documentUpdater->databaseUpdate($documentMetadata, $dossier, $document);

        $inquiryChangeset->updateInquiryNumbersForDocument(
            DocumentInquiryNumbers::fromDocumentEntity($document),
            $documentMetadata->getInquiryNumbers(),
        );
    }

    /**
     * @param array<int, Document> $documents
     */
    private function flushAndDetach(array $documents): void
    {
        $this->doctrine->flush();

        foreach ($documents as $document) {
            $this->doctrine->detach($document);
        }
    }

    private function addRowException(int $rowIndex, ProductionReportProcessRun $run, Exception $exception): void
    {
        if (! $exception instanceof TranslatableException) {
            $exception = ProcessInventoryException::forGenericRowException($exception);
        }

        $run->addRowException($rowIndex, $exception);
    }

    private function applyDeletes(InventoryChangeset $changeset, WooDecision $dossier): void
    {
        foreach ($changeset->getDeleted() as $documentNumber) {
            $document = $this->getDocument($documentNumber);
            if (! $document instanceof Document || ! $dossier->getStatus()->isConcept()) {
                throw ProductionReportUpdaterException::forStateMismatch();
            }

            // Remove the dossier-document relationship immediately, if needed the document and related files removed asynchronously
            $document->getDossiers()->removeElement($dossier);
            $this->doctrine->persist($document);
        }

        $this->doctrine->flush();
    }

    public function sendMessagesForChangeset(InventoryChangeset $changeset, WooDecision $dossier, RunProgress $runProgress): void
    {
        $this->updateWooDecisionInventories($dossier);

        $this->batchDownloadService->refresh(
            BatchDownloadScope::forWooDecision($dossier),
        );

        $this->searchDispatcher->dispatchIndexDossierCommand($dossier->getId());

        foreach ($changeset->getAll() as $documentNumber => $action) {
            $runProgress->tick();

            if ($action === InventoryChangeset::UNCHANGED) {
                continue;
            }

            $document = $this->getDocument($documentNumber);
            if (! $document instanceof Document) {
                throw ProductionReportUpdaterException::forStateMismatch();
            }

            if ($action === InventoryChangeset::DELETED) {
                $this->documentUpdater->asyncRemove($document, $dossier);
                $this->doctrine->detach($document);
                continue;
            }

            $this->documentUpdater->asyncUpdate($document);
            $this->doctrine->detach($document);
        }
    }

    public function updateWooDecisionInventories(WooDecision $dossier): void
    {
        $this->dispatcher->dispatchGenerateInventoryCommand($dossier->getId());

        foreach ($dossier->getInquiries() as $inquiry) {
            $this->wooDecisionDispatcher->dispatchGenerateInquiryInventoryCommand($inquiry->getId());
        }
    }

    private function getDocument(string $documentNumber): ?Document
    {
        return $this->documentRepository->findOneByDocumentNumberCaseInsensitive($documentNumber);
    }

    /**
     * @param array<string, array<array-key, string>> $docReferralUpdates
     */
    private function applyDocumentReferralUpdates(WooDecision $dossier, array $docReferralUpdates): void
    {
        $documentsToUpdate = [];
        foreach ($docReferralUpdates as $documentNumber => $refersTo) {
            $document = $this->getDocument($documentNumber);
            if (! $document instanceof Document) {
                throw new RuntimeException('State mismatch between database and document referral updates');
            }

            $this->documentUpdater->updateDocumentReferralsByDocumentNumber($dossier, $document, $refersTo);

            $documentsToUpdate[] = $document;
            if (count($documentsToUpdate) > 1000) {
                $this->flushAndDetach($documentsToUpdate);
                $documentsToUpdate = [];
            }
        }

        $this->flushAndDetach($documentsToUpdate);
    }
}
