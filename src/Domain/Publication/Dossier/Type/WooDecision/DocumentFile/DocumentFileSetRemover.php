<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile;

use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileSet;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Repository\DocumentFileSetRepository;
use App\Service\Storage\EntityStorageService;

readonly class DocumentFileSetRemover
{
    public function __construct(
        private DocumentFileSetRepository $documentFileSetRepository,
        private EntityStorageService $entityStorageService,
    ) {
    }

    /**
     * @return int Count of removed DocumentFileSets
     */
    public function removeAllFinalSets(): int
    {
        $count = 0;
        foreach ($this->documentFileSetRepository->findAllWithFinalStatus() as $documentFileSet) {
            $this->remove($documentFileSet);
            $count++;
        }

        return $count;
    }

    public function remove(DocumentFileSet $documentFileSet): void
    {
        $this->removeUploadFiles($documentFileSet);
        $this->removeUpdateFiles($documentFileSet);

        $this->documentFileSetRepository->remove($documentFileSet, true);
    }

    private function removeUploadFiles(DocumentFileSet $documentFileSet): void
    {
        foreach ($documentFileSet->getUploads() as $upload) {
            $this->entityStorageService->deleteAllFilesForEntity($upload);
        }
    }

    private function removeUpdateFiles(DocumentFileSet $documentFileSet): void
    {
        foreach ($documentFileSet->getUpdates() as $update) {
            $this->entityStorageService->deleteAllFilesForEntity($update);
        }
    }
}
