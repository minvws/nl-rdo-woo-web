<?php

declare(strict_types=1);

namespace App\Domain\Upload\Postprocessor\Strategy;

use App\Domain\Ingest\Process\IngestProcessOptions;
use App\Domain\Ingest\Process\SubType\SubTypeIngester;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Upload\FileType\FileType;
use App\Domain\Upload\FileType\FileTypeHelper;
use App\Domain\Upload\Postprocessor\FilePostprocessorStrategyInterface;
use App\Domain\Upload\Process\FileStorer;
use App\Domain\Upload\UploadedFile;
use App\Service\HistoryService;
use App\Utils;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
readonly class FileStrategy implements FilePostprocessorStrategyInterface
{
    public function __construct(
        private DocumentRepository $documentRepository,
        private LoggerInterface $logger,
        private SubTypeIngester $ingestService,
        private HistoryService $historyService,
        private FileStorer $fileStorer,
        private FileTypeHelper $fileTypeHandler,
    ) {
    }

    public function process(UploadedFile $file, WooDecision $dossier, string $documentId, ?string $fileType = null): void
    {
        $document = $this->documentRepository->findOneByDossierAndDocumentId($dossier, $documentId);
        if ($document === null) {
            $this->logger->info('Could not find document, skipping processing file', [
                'filename' => $file->getOriginalFilename(),
                'documentId' => $documentId,
                'dossierId' => $dossier->getId(),
            ]);

            return;
        }

        if (! $document->shouldBeUploaded($dossier->getStatus()->isPubliclyAvailable())) {
            $this->logger->warning(
                sprintf('Document with id "%s" should not be uploaded, skipping it', $documentId),
                [
                    'filename' => $file->getOriginalFilename(),
                    'documentId' => $documentId,
                    'dossierId' => $dossier->getId(),
                ]
            );

            return;
        }

        $this->fileStorer->storeForDocument(
            $file,
            $document,
            $documentId,
            $fileType ?? $file->getOriginalFileExtension(),
        );

        if ($document->isWithdrawn() && $dossier->getStatus()->isPubliclyAvailable()) {
            $document->republish();
            $this->documentRepository->save($document, true);
        }

        $this->ingestService->ingest($document, new IngestProcessOptions(forceRefresh: true));

        $this->historyService->addDocumentEntry(
            $document,
            $document->getFileInfo()->isUploaded() ? 'document_replaced' : 'document_uploaded',
            [
                'filetype' => $document->getFileInfo()->getType(),
                'filesize' => Utils::getFileSize($document),
            ],
        );
    }

    public function canProcess(UploadedFile $file, WooDecision $dossier): bool
    {
        return $this->fileTypeHandler->fileOfType(
            $file,
            FileType::PDF,
            FileType::XLS,
            FileType::DOC,
            FileType::TXT,
            FileType::PPT,
        );
    }
}
