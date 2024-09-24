<?php

declare(strict_types=1);

namespace App\Domain\Upload\Postprocessor\Strategy;

use App\Domain\Ingest\Process\IngestProcessOptions;
use App\Domain\Ingest\Process\SubType\SubTypeIngester;
use App\Domain\Upload\Postprocessor\FilePostprocessorStrategyInterface;
use App\Domain\Upload\Process\DocumentNumberExtractor;
use App\Domain\Upload\Process\FileStorer;
use App\Domain\Upload\UploadedFile;
use App\Entity\Document;
use App\Entity\Dossier;
use App\Repository\DocumentRepository;
use App\Service\HistoryService;
use App\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final readonly class FilePdfStrategy implements FilePostprocessorStrategyInterface
{
    public function __construct(
        private EntityManagerInterface $doctrine,
        private LoggerInterface $logger,
        private SubTypeIngester $ingestService,
        private HistoryService $historyService,
        private DocumentNumberExtractor $documentNumberExtractor,
        private FileStorer $fileStorer,
    ) {
    }

    public function process(UploadedFile $file, Dossier $dossier): void
    {
        $documentId = $this->documentNumberExtractor->extract($file->getOriginalFilename(), $dossier);

        // Find matching document entity in the database
        /** @var DocumentRepository $documentRepository */
        $documentRepository = $this->doctrine->getRepository(Document::class);
        $document = $documentRepository->findOneByDossierAndDocumentId($dossier, $documentId);
        if ($document === null) {
            // Document does not exist. That is actually fine.
            $this->logger->info('Could not find document, skipping processing file', [
                'filename' => $file->getOriginalFilename(),
                'documentId' => $documentId,
                'dossierId' => $dossier->getId(),
            ]);

            return;
        }

        if (! $document->shouldBeUploaded()) {
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

        $replaced = $document->getFileInfo()->isUploaded();

        $this->fileStorer->storeForDocument($file, $document, $documentId, $file->getOriginalFileExtension());

        $options = new IngestProcessOptions();
        $options->setForceRefresh(true);
        $this->ingestService->ingest($document, $options);

        $this->historyService->addDocumentEntry(
            $document,
            $replaced ? 'document_replaced' : 'document_uploaded',
            [
                'filetype' => $document->getFileInfo()->getType(),
                'filesize' => Utils::getFileSize($document),
            ],
        );
    }

    public function canProcess(UploadedFile $file, Dossier $dossier): bool
    {
        return $file->getOriginalFileExtension() === 'pdf';
    }
}
