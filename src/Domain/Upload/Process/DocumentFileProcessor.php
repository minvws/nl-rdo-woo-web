<?php

declare(strict_types=1);

namespace App\Domain\Upload\Process;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Upload\UploadedFile;
use App\Entity\Document;
use Psr\Log\LoggerInterface;

final readonly class DocumentFileProcessor
{
    public function __construct(
        private LoggerInterface $logger,
        private DocumentNumberExtractor $documentNumberExtractor,
        private FileStorer $fileStorer,
    ) {
    }

    public function process(
        UploadedFile $file,
        WooDecision $dossier,
        Document $document,
        string $type,
    ): void {
        $documentId = $this->documentNumberExtractor->extract($file->getOriginalFilename(), $dossier);

        if ($document->getDocumentId() !== $documentId) {
            $this->logger->warning(
                sprintf('Filename does not match the document with id %s', $documentId),
                [
                    'filename' => $file->getOriginalFilename(),
                    'documentId' => $documentId,
                    'dossierId' => $dossier->getId(),
                ],
            );

            return;
        }

        $this->fileStorer->storeForDocument($file, $document, $documentId, $type);
    }
}
