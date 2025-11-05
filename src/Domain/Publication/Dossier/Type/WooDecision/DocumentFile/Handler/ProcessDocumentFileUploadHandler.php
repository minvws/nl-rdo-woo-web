<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Handler;

use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Command\ProcessDocumentFileUploadCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\DocumentFileService;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileSet;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileUpdate;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Repository\DocumentFileUpdateRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Repository\DocumentFileUploadRepository;
use App\Domain\Upload\FileType\MimeTypeHelper;
use App\Domain\Upload\Preprocessor\FilePreprocessor;
use App\Domain\Upload\Process\DocumentNumberExtractor;
use App\Domain\Upload\UploadedFile;
use App\Service\Storage\EntityStorageService;
use App\Service\Uploader\UploadGroupId;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
#[AsMessageHandler]
readonly class ProcessDocumentFileUploadHandler
{
    public function __construct(
        private DocumentFileUploadRepository $documentFileUploadRepository,
        private DocumentFileUpdateRepository $documentFileUpdateRepository,
        private LoggerInterface $logger,
        private FilePreprocessor $filePreprocessor,
        private EntityStorageService $entityStorageService,
        private DocumentNumberExtractor $documentNumberExtractor,
        private DocumentFileService $documentFileService,
        private MimeTypeHelper $mimeTypeHelper,
    ) {
    }

    public function __invoke(ProcessDocumentFileUploadCommand $command): void
    {
        $documentFileUpload = $this->documentFileUploadRepository->find($command->id);
        if ($documentFileUpload === null) {
            $this->logger->warning('No DocumentFileUpload found for this command', [
                'id' => $command->id,
            ]);

            return;
        }

        if (! $documentFileUpload->getStatus()->isUploaded()) {
            return;
        }

        $localFile = $this->entityStorageService->downloadEntity($documentFileUpload);
        if ($localFile === false) {
            $this->logger->warning('No file could be downloaded for DocumentFileUpload', [
                'id' => $command->id,
            ]);

            return;
        }

        $documentFileSet = $documentFileUpload->getDocumentFileSet();

        $fileIterator = $this->filePreprocessor->process(
            new UploadedFile(
                $localFile,
                $documentFileUpload->getFileInfo()->getName(),
            ),
        );

        $this->handleFiles($fileIterator, $documentFileSet);

        // Remove the upload file as this has now been 'forwarded' to the DocumentFileUpdate entity
        $this->entityStorageService->deleteAllFilesForEntity($documentFileUpload);
        $this->entityStorageService->removeDownload($localFile);
        $documentFileUpload->getFileInfo()->removeFileProperties();

        $documentFileUpload->markAsProcessed();
        $this->documentFileUploadRepository->save($documentFileUpload, true);

        $this->documentFileService->checkProcessingUploadsCompletion($documentFileSet);
    }

    /**
     * @param \Generator<array-key,UploadedFile> $fileIterator
     */
    private function handleFiles(\Generator $fileIterator, DocumentFileSet $documentFileSet): void
    {
        $dossier = $documentFileSet->getDossier();
        foreach ($fileIterator as $file) {
            /** @var UploadedFile $file */
            $originalFileExtension = $file->getOriginalFileExtension();
            $mimeType = $this->mimeTypeHelper->detectMimeTypeFromPath($file);

            if (! $this->mimeTypeHelper->isValidForUploadGroup($originalFileExtension, $mimeType, UploadGroupId::WOO_DECISION_DOCUMENTS)) {
                $this->logger->info('Unsupported mimetype, skipping file', [
                    'filename' => $file->getOriginalFilename(),
                    'dossierId' => $dossier->getId(),
                ]);

                continue;
            }

            $document = $this->documentNumberExtractor->matchDocumentForFile($file, $dossier);
            if ($document === null) {
                continue;
            }

            if ($this->documentFileUpdateRepository->hasUpdateForFileSetAndDocument($documentFileSet, $document)) {
                $this->logger->info('Document already being updated, skipping file', [
                    'filename' => $file->getOriginalFilename(),
                    'dossierId' => $dossier->getId(),
                ]);

                continue;
            }

            if (! $document->shouldBeUploaded($dossier->getStatus()->isPublished())) {
                $this->logger->info('Document should not be uploaded, skipping file', [
                    'filename' => $file->getOriginalFilename(),
                    'dossierId' => $dossier->getId(),
                ]);

                continue;
            }

            $documentFileUpdate = new DocumentFileUpdate($documentFileSet, $document);
            $this->entityStorageService->storeEntity($file, $documentFileUpdate, false);

            $documentFileUpdate->getFileInfo()->setName($file->getOriginalFilename());
            $documentFileUpdate->getFileInfo()->setType($originalFileExtension);
            $this->documentFileUpdateRepository->save($documentFileUpdate, true);
        }
    }
}
