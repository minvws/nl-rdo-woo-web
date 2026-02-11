<?php

declare(strict_types=1);

namespace Shared\Domain\Upload\Process;

use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Upload\UploadedFile;

use function basename;
use function is_null;
use function preg_match;

readonly class DocumentNumberExtractor
{
    public function __construct(
        private LoggerInterface $logger,
        private DocumentRepository $documentRepository,
    ) {
    }

    public function extract(string $originalFile, WooDecision $dossier): string
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

    public function matchDocumentForFile(UploadedFile $file, WooDecision $wooDecision): ?Document
    {
        try {
            $documentId = $this->extract(
                $file->getOriginalFilename(),
                $wooDecision,
            );
        } catch (FileProcessException) {
            return null;
        }

        return $this->documentRepository->findOneByDossierAndDocumentId($wooDecision, $documentId);
    }
}
