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

    public function matchDocumentForFile(UploadedFile $file, WooDecision $wooDecision): ?Document
    {
        $filename = pathinfo($file->getOriginalFilename(), PATHINFO_FILENAME);

        $document = $this->findDocument($filename, $wooDecision);
        if ($document !== null) {
            return $document;
        }

        return $this->findDocumentForFilenamePrefix($filename, $wooDecision);
    }

    private function findDocumentForFilenamePrefix(string $filename, WooDecision $wooDecision): ?Document
    {
        if (preg_match('/^([a-zA-Z0-9]+)/', $filename, $matches) !== 1) {
            $this->logger->error('Cannot extract document ID from the filename', [
                'filename' => $filename,
                'dossierId' => $wooDecision->getId(),
            ]);

            return null;
        }

        return $this->findDocument($matches[1], $wooDecision);
    }

    private function findDocument(string $documentIdString, WooDecision $wooDecision): ?Document
    {
        try {
            $documentId = DocumentId::create($documentIdString);
        } catch (InvalidArgumentException) {
            return null;
        }

        return $this->documentRepository->findOneByDossierAndDocumentId($wooDecision, $documentId);
    }
}
