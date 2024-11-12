<?php

declare(strict_types=1);

namespace App\Service\Inquiry;

use App\Domain\Ingest\IngestDispatcher;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionDispatcher;
use App\Domain\Search\SearchDispatcher;
use App\Entity\BatchDownload;
use App\Entity\Document;
use App\Entity\Inquiry;
use App\Entity\InquiryInventory;
use App\Entity\Organisation;
use App\Message\GenerateInquiryArchivesMessage;
use App\Service\BatchDownloadService;
use App\Service\HistoryService;
use App\Service\Inventory\InquiryChangeset;
use App\Service\Storage\EntityStorageService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
readonly class InquiryService
{
    public function __construct(
        private EntityManagerInterface $doctrine,
        private MessageBusInterface $messageBus,
        private BatchDownloadService $batchDownloadService,
        private EntityStorageService $entityStorageService,
        private HistoryService $historyService,
        private SearchDispatcher $searchDispatcher,
        private WooDecisionDispatcher $wooDecisionDispatcher,
        private IngestDispatcher $ingestDispatcher,
    ) {
    }

    public function findOrCreateInquiryForCaseNumber(Organisation $organisation, string $caseNumber): Inquiry
    {
        $inquiry = $this->doctrine->getRepository(Inquiry::class)->findOneBy(['organisation' => $organisation, 'casenr' => $caseNumber]);

        if (! $inquiry) {
            $inquiry = new Inquiry();
            $inquiry->setCasenr($caseNumber);
            $inquiry->setOrganisation($organisation);

            $this->doctrine->persist($inquiry);
            $this->doctrine->flush();
        }

        return $inquiry;
    }

    /**
     * Removes the given dossier from all inquiries that are currently linked to it.
     * If no other dossiers remain in the inquiry it will be removed.
     */
    public function removeDossierFromInquiries(WooDecision $dossier): void
    {
        foreach ($this->doctrine->getRepository(Inquiry::class)->findByDossier($dossier) as $inquiry) {
            /** @var Inquiry $inquiry */
            $inquiry->removeDossier($dossier);

            if ($inquiry->getDossiers()->isEmpty()) {
                $inventory = $inquiry->getInventory();
                if ($inventory instanceof InquiryInventory) {
                    $this->entityStorageService->removeFileForEntity($inventory);
                    $this->doctrine->remove($inventory);
                }
                $this->batchDownloadService->removeAllDownloadsForEntity($dossier);
                $this->doctrine->remove($inquiry);
            } else {
                $this->doctrine->persist($inquiry);

                $this->generateInventory($inquiry);
                $this->generateArchives($inquiry);
            }
        }

        $this->doctrine->flush();
    }

    public function generateInventory(Inquiry $inquiry): void
    {
        $this->wooDecisionDispatcher->dispatchGenerateInquiryInventoryCommand($inquiry->getId());
    }

    public function generateArchives(Inquiry $inquiry): void
    {
        $this->messageBus->dispatch(
            GenerateInquiryArchivesMessage::forInquiry($inquiry)
        );
    }

    public function generateBatch(Inquiry $inquiry, QueryBuilder $query): ?BatchDownload
    {
        $documentNrs = [];

        /** @var Document[] $documents */
        $documents = $query->select('doc')->getQuery()->getResult();
        foreach ($documents as $document) {
            if ($document->shouldBeUploaded() && $document->isUploaded()) {
                $documentNrs[] = $document->getDocumentNr();
            }
        }

        if (count($documentNrs) === 0) {
            return null;
        }

        return $this->batchDownloadService->findOrCreate($inquiry, $documentNrs, false);
    }

    /**
     * @param Uuid[] $docIdsToAdd
     * @param Uuid[] $docIdsToDelete
     * @param Uuid[] $dossierIdsToAdd
     */
    public function updateInquiryLinks(
        Organisation $organisation,
        string $caseNr,
        array $docIdsToAdd,
        array $docIdsToDelete,
        array $dossierIdsToAdd,
    ): void {
        $inquiry = $this->findOrCreateInquiryForCaseNumber($organisation, $caseNr);
        $result = new InquiryLinkUpdateResult($inquiry, $caseNr);

        foreach ($docIdsToAdd as $docIdToAdd) {
            $this->handleDocumentAdd($docIdToAdd, $result);
        }

        foreach ($docIdsToDelete as $docIdToDelete) {
            $this->handleDocumentDelete($docIdToDelete, $result);
        }

        foreach ($dossierIdsToAdd as $dossierId) {
            $this->handleDossierAdd($dossierId, $result);
        }

        if ($result->hasAddedDossiers()) {
            $this->historyService->addInquiryEntry($inquiry, 'dossiers_added', ['count' => $result->getAddedDossierCount()]);
        }
        if (count($docIdsToAdd) > 0) {
            $this->historyService->addInquiryEntry($inquiry, 'documents_added', ['count' => count($docIdsToAdd)]);
        }

        $this->doctrine->persist($inquiry);
        $this->doctrine->flush();

        if ($result->needsFileUpdate()) {
            $this->generateInventory($inquiry);
            $this->generateArchives($inquiry);
        }

        $this->dispatchDocumentUpdates($result);
        $this->dispatchDossierUpdates($result);
    }

    public function applyChangesetAsync(InquiryChangeset $changeset): void
    {
        foreach ($changeset->getChanges() as $caseNr => $actions) {
            $this->wooDecisionDispatcher->dispatchUpdateInquiryLinksCommand(
                $changeset->getOrganisation()->getId(),
                strval($caseNr),
                $actions[InquiryChangeset::ADD_DOCUMENTS],
                $actions[InquiryChangeset::DEL_DOCUMENTS],
                $actions[InquiryChangeset::ADD_DOSSIERS],
            );
        }
    }

    private function addDossierToInquiry(Inquiry $inquiry, ?WooDecision $dossier, string $caseNr): bool
    {
        if (! $dossier || $inquiry->getDossiers()->contains($dossier)) {
            return false;
        }

        $inquiry->addDossier($dossier);

        $this->historyService->addDossierEntry($dossier, 'dossier_inquiry_added', ['count' => 1, 'casenrs' => $caseNr]);

        return $dossier->getStatus()->isPubliclyAvailable();
    }

    private function dispatchDocumentUpdates(InquiryLinkUpdateResult $result): void
    {
        foreach ($result->getUpdatedDocumentIds() as $id) {
            $this->ingestDispatcher->dispatchIngestMetadataOnlyCommand($id, Document::class, false);
        }
    }

    private function dispatchDossierUpdates(InquiryLinkUpdateResult $result): void
    {
        foreach ($result->getUpdatedDossierIds() as $id) {
            $this->searchDispatcher->dispatchIndexDossierCommand($id);
        }
    }

    private function handleDocumentAdd(
        Uuid $documentId,
        InquiryLinkUpdateResult $result,
    ): void {
        $document = $this->doctrine->getRepository(Document::class)->find($documentId);
        if ($document === null) {
            return;
        }

        $inquiry = $result->getInquiry();
        $inquiry->addDocument($document);
        foreach ($document->getDossiers() as $dossier) {
            if ($this->addDossierToInquiry($inquiry, $dossier, $result->getCaseNr())) {
                $result->dossierAdded($dossier);
            }
        }

        $result->documentAdded($document);
    }

    private function handleDocumentDelete(
        Uuid $documentId,
        InquiryLinkUpdateResult $result,
    ): void {
        $document = $this->doctrine->getRepository(Document::class)->find($documentId);
        if ($document === null) {
            return;
        }

        $result->getInquiry()->removeDocument($document);
        $result->documentRemoved($document);
    }

    private function handleDossierAdd(
        Uuid $dossierId,
        InquiryLinkUpdateResult $result,
    ): void {
        $dossier = $this->doctrine->getRepository(WooDecision::class)->find($dossierId);
        if ($dossier === null) {
            return;
        }

        if ($this->addDossierToInquiry($result->getInquiry(), $dossier, $result->getCaseNr())) {
            $result->dossierAdded($dossier);
        }
    }
}
