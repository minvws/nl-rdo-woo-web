<?php

declare(strict_types=1);

namespace Shared\Domain\Upload\Process;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Upload\UploadedFile;
use Shared\ValueObject\DocumentId;

use function pathinfo;
use function preg_match;

use const PATHINFO_FILENAME;

readonly class DocumentNumberExtractor
{
    public function __construct(
        private LoggerInterface $logger,
        private DocumentRepository $documentRepository,
    ) {
    }

    public function extract(string $originalFile, WooDecision $dossier): DocumentId
    {
        $originalFileName = pathinfo($originalFile, PATHINFO_FILENAME);

        preg_match('/^([a-zA-Z0-9]+)/', $originalFileName, $matches);
        $documentIdString = $matches[1] ?? null;

        try {
            return DocumentId::create($documentIdString ?? '');
        } catch (InvalidArgumentException) {
            $this->logger->error('Cannot extract document ID from the filename', [
                'filename' => $originalFile,
                'dossierId' => $dossier->getId(),
            ]);

            throw FileProcessException::forFailingToExtractDocumentId($originalFile, $dossier);
        }
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
