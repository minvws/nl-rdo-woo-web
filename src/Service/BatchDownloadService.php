<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\BatchDownload;
use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\EntityWithBatchDownload;
use App\Entity\Inquiry;
use App\Message\GenerateArchiveMessage;
use App\Repository\BatchDownloadRepository;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class BatchDownloadService
{
    public function __construct(
        private readonly BatchDownloadRepository $batchRepository,
        private readonly MessageBusInterface $messageBus,
        private readonly ArchiveService $archiveService,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function refreshForEntity(EntityWithBatchDownload $entity): void
    {
        $this->removeAllDownloadsForEntity($entity);

        if (! $entity->isAvailableForBatchDownload()) {
            return;
        }

        $this->findOrCreate($entity, [], false);
    }

    /**
     * When no documents are passed (empty array) all entity documents will be included.
     *
     * @param string[] $documentNrs
     */
    public function findOrCreate(EntityWithBatchDownload $entity, array $documentNrs, bool $customSelection): BatchDownload
    {
        // Complete archives get a much longer lifetime, as they will be used frequently
        $expiration = new \DateTimeImmutable($customSelection ? '+48 hours' : '+10 years');

        // No documents mean all documents of the entity
        if (count($documentNrs) === 0) {
            /** @var Document $document */
            foreach ($entity->getDocuments() as $document) {
                if (! $document->shouldBeUploaded() || ! $document->isUploaded()) {
                    continue;
                }

                $documentNrs[] = $document->getDocumentNr();
            }
        }

        // If a batch already exists with the given documents, re-use this and return early.
        $batch = $this->exists($entity, $documentNrs);
        if ($batch) {
            return $batch;
        }

        $batch = new BatchDownload();
        $batch->setStatus(BatchDownload::STATUS_PENDING);
        $batch->setEntity($entity);
        $batch->setDownloaded(0);
        $batch->setExpiration($expiration);
        $batch->setDocuments($documentNrs);
        $batch->setFilename(
            $this->getFilename($entity, $documentNrs)
        );

        $this->batchRepository->save($batch);

        // Dispatch message to generate archive
        $this->messageBus->dispatch(
            new GenerateArchiveMessage($batch->getId())
        );

        return $batch;
    }

    public function remove(BatchDownload $batch): void
    {
        $this->archiveService->removeZip($batch);
        $this->batchRepository->remove($batch);
    }

    /**
     * @param string[] $documents
     */
    private function exists(EntityWithBatchDownload $entity, array $documents): ?BatchDownload
    {
        // Prune all expired documents (garbage collection in case cron doesn't work)
        $this->batchRepository->pruneExpired();

        $batches = $this->getExistingBatches($entity);
        foreach ($batches as $batch) {
            // Both completed and pending are ok. Otherwise we get a stampede when a zip is being created and the user refreshes the page.
            if (
                $batch->getStatus() !== BatchDownload::STATUS_COMPLETED
                && $batch->getStatus() !== BatchDownload::STATUS_PENDING
            ) {
                continue;
            }

            if ($batch->getDocuments() === $documents) {
                return $batch;
            }
        }

        return null;
    }

    public function removeAllDownloadsForEntity(EntityWithBatchDownload $entity): void
    {
        foreach ($this->getExistingBatches($entity) as $batch) {
            $this->remove($batch);
        }
    }

    /**
     * @return BatchDownload[]
     */
    private function getExistingBatches(EntityWithBatchDownload $entity): array
    {
        $criteria = match (true) {
            $entity instanceof Dossier => ['dossier' => $entity],
            $entity instanceof Inquiry => ['inquiry' => $entity],
            default => throw new \OutOfBoundsException('Unsupported entity for batchdownload'),
        };

        return $this->batchRepository->findBy($criteria);
    }

    /**
     * @param string[] $documentNrs
     */
    private function getFilename(EntityWithBatchDownload $entity, array $documentNrs): string
    {
        $prefix = $entity->getDownloadFilePrefix();
        $translatedPrefix = $this->translator->trans(
            $prefix->getMessage(),
            $prefix->getPlaceholders()
        );

        $filename = sprintf(
            '%s-%s.zip',
            $translatedPrefix,
            hash('sha256', $entity->getId()?->toBase58() . serialize($documentNrs))
        );

        $sanitizer = new FilenameSanitizer($filename);
        $sanitizer->stripAdditionalCharacters();
        $sanitizer->stripIllegalFilesystemCharacters();
        $sanitizer->stripRiskyCharacters();

        return $sanitizer->getFilename();
    }
}
