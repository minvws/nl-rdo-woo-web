<?php

declare(strict_types=1);

namespace App\Domain\Upload\Process;

use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Document;
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

    public function storeForDocument(\SplFileInfo $file, Document $document, string $documentId, string $type): void
    {
        if (! $this->entityStorageService->storeEntity($file, $document)) {
            $this->logger->error('Failed to store document', [
                'documentId' => $documentId,
                'path' => $file->getPathname(),
            ]);

            throw FileProcessException::forFailingToStoreDocument($file, $documentId);
        }

        $document->getFileInfo()->setType($type);

        $this->doctrine->persist($document);
        $this->doctrine->flush();
    }
}
