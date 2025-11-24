<?php

declare(strict_types=1);

namespace Shared\Domain\Upload\Process;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Upload\UploadedFile;
use Shared\Service\Storage\EntityStorageService;
use Shared\Service\Storage\ThumbnailStorageService;

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
