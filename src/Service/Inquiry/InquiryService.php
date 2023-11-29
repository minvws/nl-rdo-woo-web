<?php

declare(strict_types=1);

namespace App\Service\Inquiry;

use App\Entity\BatchDownload;
use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\Inquiry;
use App\Entity\InquiryInventory;
use App\Message\GenerateInquiryArchivesMessage;
use App\Message\GenerateInquiryInventoryMessage;
use App\Service\BatchDownloadService;
use App\Service\Storage\DocumentStorageService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Messenger\MessageBusInterface;

class InquiryService
{
    /**
     * This local lookup exists for two reasons:
     * - Performance vs database lookup. For big inventories this service is called thousands of times in a row, with many reuse of inquiries.
     * - The findOrCreateInquiryForCaseNumber doesn't flush (for performance) so a newly created inquiry would not be found in next iteration.
     *
     * @var array<string, Inquiry>
     */
    private array $inquiries = [];

    public function __construct(
        private readonly EntityManagerInterface $doctrine,
        private readonly MessageBusInterface $messageBus,
        private readonly BatchDownloadService $batchDownloadService,
        private readonly DocumentStorageService $storageService,
    ) {
    }

    public function clearLookupCache(): void
    {
        $this->inquiries = [];
    }

    /**
     * If the document has case numbers, add it to those inquiries. If those inquiries do not exist
     * yet, create them as well.
     *
     * @param array<string, string> $caseNrs
     */
    public function updateDocumentInquiries(Document $document, array $caseNrs): void
    {
        foreach ($caseNrs as $caseNr) {
            $inquiry = $this->findOrCreateInquiryForCaseNumber($caseNr);

            // Add this document, and the dossiers it belongs to, to the inquiry
            $inquiry->addDocument($document);
            foreach ($document->getDossiers() as $dossier) {
                $inquiry->addDossier($dossier);
            }

            $this->doctrine->persist($inquiry);

            $this->generateInventory($inquiry);
            $this->generateArchives($inquiry);
        }
    }

    /**
     * @param string[] $caseNrs
     */
    public function addDossierToInquiries(Dossier $dossier, array $caseNrs): void
    {
        foreach ($caseNrs as $caseNr) {
            $inquiry = $this->findOrCreateInquiryForCaseNumber($caseNr);

            $inquiry->addDossier($dossier);
            $this->doctrine->persist($inquiry);

            if ($dossier->getStatus() === Dossier::STATUS_PUBLISHED || $dossier->getStatus() === Dossier::STATUS_PREVIEW) {
                $this->generateInventory($inquiry);
                $this->generateArchives($inquiry);
            }
        }
    }

    public function findOrCreateInquiryForCaseNumber(string $caseNumber): Inquiry
    {
        if (array_key_exists($caseNumber, $this->inquiries)) {
            return $this->inquiries[$caseNumber];
        }

        $inquiry = $this->doctrine->getRepository(Inquiry::class)->findOneBy(['casenr' => $caseNumber]);

        if (! $inquiry) {
            $inquiry = new Inquiry();
            $inquiry->setCasenr($caseNumber);

            $this->doctrine->persist($inquiry);
        }

        $this->inquiries[$inquiry->getCasenr()] = $inquiry;

        return $inquiry;
    }

    public function flush(): void
    {
        $this->doctrine->flush();
    }

    /**
     * Removes the given dossier from all inquiries that are currently linked to it.
     * If no other dossiers remain in the inquiry it will be removed.
     */
    public function removeDossierFromInquiries(Dossier $dossier): void
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
}
