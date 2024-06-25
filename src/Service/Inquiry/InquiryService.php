<?php

declare(strict_types=1);

namespace App\Service\Inquiry;

use App\Domain\Ingest\IngestMetadataOnlyMessage;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Search\Index\IndexDossierMessage;
use App\Entity\BatchDownload;
use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\Inquiry;
use App\Entity\InquiryInventory;
use App\Entity\Organisation;
use App\Message\GenerateInquiryArchivesMessage;
use App\Message\GenerateInquiryInventoryMessage;
use App\Message\UpdateInquiryLinksMessage;
use App\Service\BatchDownloadService;
use App\Service\HistoryService;
use App\Service\Inventory\InquiryChangeset;
use App\Service\Storage\DocumentStorageService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
class InquiryService
{
    public function __construct(
        private readonly EntityManagerInterface $doctrine,
        private readonly MessageBusInterface $messageBus,
        private readonly BatchDownloadService $batchDownloadService,
        private readonly DocumentStorageService $storageService,
        private readonly HistoryService $historyService,
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
                    $this->storageService->removeFileForEntity($inventory);
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
        $this->messageBus->dispatch(
            GenerateInquiryInventoryMessage::forInquiry($inquiry)
        );
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
        $updateFiles = false;
        $updatedDossiers = [];
        $updatedDocuments = [];
        $inquiry = $this->findOrCreateInquiryForCaseNumber($organisation, $caseNr);

        $dossiersAdded = 0;

        foreach ($docIdsToAdd as $docIdToAdd) {
            $document = $this->doctrine->getRepository(Document::class)->find($docIdToAdd);
            if ($document === null) {
                continue;
            }

            $inquiry->addDocument($document);
            foreach ($document->getDossiers() as $dossier) {
                if ($this->addDossierToInquiry($inquiry, $dossier, $caseNr)) {
                    $dossiersAdded++;
                    $updatedDossiers[] = $dossier->getId();
                    $updateFiles = true;
                }
            }

            $updatedDocuments[] = $document->getId();
        }

        foreach ($docIdsToDelete as $docIdToDelete) {
            $document = $this->doctrine->getRepository(Document::class)->find($docIdToDelete);
            if ($document === null) {
                continue;
            }

            $inquiry->removeDocument($document);

            $updatedDocuments[] = $document->getId();
            $updateFiles = true;
        }

        foreach ($dossierIdsToAdd as $dossierId) {
            $dossier = $this->doctrine->getRepository(WooDecision::class)->find($dossierId);
            if ($dossier !== null && $this->addDossierToInquiry($inquiry, $dossier, $caseNr)) {
                $dossiersAdded++;
                $updatedDossiers[] = $dossier->getId();
                $updateFiles = true;
            }
        }

        if ($dossiersAdded > 0) {
            $this->historyService->addInquiryEntry($inquiry, 'dossiers_added', ['count' => $dossiersAdded]);
        }
        if (count($docIdsToAdd) > 0) {
            $this->historyService->addInquiryEntry($inquiry, 'documents_added', ['count' => count($docIdsToAdd)]);
        }

        $this->doctrine->persist($inquiry);
        $this->doctrine->flush();

        if ($updateFiles) {
            $this->generateInventory($inquiry);
            $this->generateArchives($inquiry);
        }

        $this->updateDocuments($updatedDocuments);
        $this->updateDossiers($updatedDossiers);
    }

    public function applyChangesetAsync(InquiryChangeset $changeset): void
    {
        foreach ($changeset->getChanges() as $caseNr => $actions) {
            $this->messageBus->dispatch(
                new UpdateInquiryLinksMessage(
                    $changeset->getOrganisation()->getId(),
                    strval($caseNr),
                    $actions[InquiryChangeset::ADD_DOCUMENTS],
                    $actions[InquiryChangeset::DEL_DOCUMENTS],
                    $actions[InquiryChangeset::ADD_DOSSIERS],
                )
            );
        }
    }

    private function addDossierToInquiry(Inquiry $inquiry, ?Dossier $dossier, string $caseNr): bool
    {
        if (! $dossier || $inquiry->getDossiers()->contains($dossier)) {
            return false;
        }

        $inquiry->addDossier($dossier);

        $this->historyService->addDossierEntry($dossier, 'dossier_inquiry_added', ['count' => 1, 'casenrs' => $caseNr]);

        return $dossier->getStatus()->isPubliclyAvailable();
    }

    /**
     * @param array<array-key,Uuid> $ids
     */
    private function updateDocuments(array $ids): void
    {
        foreach ($ids as $id) {
            $this->messageBus->dispatch(new IngestMetadataOnlyMessage($id, Document::class));
        }
    }

    /**
     * @param array<array-key,Uuid> $ids
     */
    private function updateDossiers(array $ids): void
    {
        foreach ($ids as $id) {
            $this->messageBus->dispatch(new IndexDossierMessage($id));
        }
    }
}
