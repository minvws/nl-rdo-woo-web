<?php

declare(strict_types=1);

namespace App\Domain\Upload\Process;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Upload\UploadedFile;
use App\Service\Storage\EntityStorageService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

readonly class FileStorer
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $doctrine,
        private EntityStorageService $entityStorageService,
    ) {
    }

    public function storeForDocument(UploadedFile $file, Document $document, string $documentId): void
    {
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
        $document->setPageCount(0);

        $this->doctrine->persist($document);
        $this->doctrine->flush();
    }
}
