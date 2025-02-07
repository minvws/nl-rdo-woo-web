<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Handler;

use App\Domain\Publication\Dossier\Type\WooDecision\Command\ProcessDocumentFileUploadCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFileService;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\DocumentFileUpdate;
use App\Domain\Publication\Dossier\Type\WooDecision\Enum\DocumentFileUploadStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\Repository\DocumentFileUpdateRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\Repository\DocumentFileUploadRepository;
use App\Domain\Upload\Preprocessor\FilePreprocessor;
use App\Domain\Upload\Process\DocumentNumberExtractor;
use App\Domain\Upload\UploadedFile;
use App\Service\Storage\EntityStorageService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

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
        $dossier = $documentFileSet->getDossier();

        $fileIterator = $this->filePreprocessor->process(
            new UploadedFile(
                $localFile,
                $documentFileUpload->getFileInfo()->getName(),
            ),
        );

        foreach ($fileIterator as $file) {
            $document = $this->documentNumberExtractor->matchDocumentForFile($file, $dossier);
            if ($document === null) {
                $this->logger->info('Could not find document, skipping processing file', [
                    'filename' => $file->getOriginalFilename(),
                    'dossierId' => $dossier->getId(),
                ]);

                continue;
            }

            // TODO is optimization needed big datasets? For example flush in batches and clear entitymanager
            $documentFileUpdate = new DocumentFileUpdate($documentFileSet, $document);
            $this->entityStorageService->storeEntity($file, $documentFileUpdate, false);

            $documentFileUpdate->getFileInfo()->setName($file->getOriginalFilename());
            $documentFileUpdate->getFileInfo()->setType($file->getOriginalFileExtension());
            $this->documentFileUpdateRepository->save($documentFileUpdate, true);
        }

        $documentFileUpload->setStatus(DocumentFileUploadStatus::PROCESSED);
        $this->documentFileUploadRepository->save($documentFileUpload, true);

        $this->documentFileService->checkProcessingUploadsCompletion($documentFileSet);

        // Remove the upload file as this has now been 'forwarded' to the DocumentFileUpdate entity
        $this->entityStorageService->removeFileForEntity($documentFileUpload);
        $this->entityStorageService->removeDownload($localFile);
    }
}
