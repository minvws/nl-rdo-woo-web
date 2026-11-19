<?php

declare(strict_types=1);

namespace Shared\Domain\Upload\Process;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Service\Storage\EntityStorageService;
use Shared\Service\Storage\ThumbnailStorageService;
use Shared\ValueObject\DocumentId;

readonly class FileStorer
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $doctrine,
        private EntityStorageService $entityStorageService,
        private ThumbnailStorageService $thumbnailStorage,
    ) {
    }

    public function storeForDocument(EntityWithFileInfo $source, Document $document, DocumentId $documentId): void
    {
        if ($document->getFileInfo()->isUploaded()) {
            $this->thumbnailStorage->deleteAllThumbsForEntity($document);
        }

        if (! $this->entityStorageService->copyEntity($source, $document)) {
            $this->logger->error('Failed to store document', [
                'documentId' => $documentId->toString(),
                'path' => $source->getFileInfo()->getPath(),
            ]);

            throw FileProcessException::forFailingToStoreDocument($source, $documentId);
        }

        $fileInfo = $document->getFileInfo();
        $fileInfo->setType($source->getFileInfo()->getType());
        $fileInfo->setPageCount(null);

        $this->doctrine->persist($document);
        $this->doctrine->flush();
    }
}
