<?php

declare(strict_types=1);

namespace App\Domain\Upload\Process;

use App\Entity\Dossier;
use Psr\Log\LoggerInterface;

readonly class DocumentNumberExtractor
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function extract(string $originalFile, Dossier $dossier): string
    {
        $originalFile = basename($originalFile);
        preg_match('/^([a-zA-Z0-9\-]+)/', $originalFile, $matches);
        $documentId = $matches[1] ?? null;

        if (is_null($documentId)) {
            $this->logger->error('Cannot extract document ID from the filename', [
                'filename' => $originalFile,
                'matches' => $matches,
                'dossierId' => $dossier->getId(),
            ]);

            throw FileProcessException::forFailingToExtractDocumentId($originalFile, $dossier);
        }

        return $documentId;
    }
}
