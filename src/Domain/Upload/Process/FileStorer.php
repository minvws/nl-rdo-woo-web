<?php

declare(strict_types=1);

namespace App\Domain\Upload\Process;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Upload\UploadedFile;
use App\Service\Storage\EntityStorageService;
use App\Service\Storage\ThumbnailStorageService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

readonly class FileStorer
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $doctrine,
        private EntityStorageService $entityStorageService,
        private ThumbnailStorageService $thumbnailStorage,
    ) {
    }

    public function storeForDocument(UploadedFile $file, Document $document, string $documentId): void
    {
        if ($document->getFileInfo()->isUploaded()) {
            $this->thumbnailStorage->deleteAllThumbsForEntity($document);
        }

        if (! $this->entityStorageService->storeEntity($file, $document)) {
            $this->logger->error('Failed to store document', [
                'documentId' => $documentId,
                'path' => $file->getPathname(),
            ]);

            throw FileProcessException::forFailingToStoreDocument($file, $documentId);
        }

        $fileInfo = $document->getFileInfo();
        $fileInfo->setType($file->getOriginalFileExtension());
        $fileInfo->setPageCount(null);

        $this->doctrine->persist($document);
        $this->doctrine->flush();
    }
}
