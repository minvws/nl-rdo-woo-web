<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Handler;

use App\Domain\Publication\Dossier\Type\WooDecision\Command\ProcessDocumentCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\Repository\WooDecisionRepository;
use App\Domain\Upload\Process\FileProcessor;
use App\Domain\Upload\UploadedFile;
use App\Service\DocumentUploadQueue;
use App\Service\DossierService;
use App\Service\Storage\EntityStorageService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Process a document (archive, pdf) that is uploaded to the system. If the upload has been chunked, it will be stitched together first.
 *
 * @codeCoverageIgnore Currently hard to test due to chunked file handling, which will be removed in woo-3346
 */
#[AsMessageHandler]
class ProcessDocumentHandler
{
    use AssembleChunksTrait;

    public function __construct(
        private readonly EntityStorageService $entityStorageService,
        private readonly WooDecisionRepository $wooDecisionRepository,
        private readonly LoggerInterface $logger,
        private readonly DocumentUploadQueue $uploadQueue,
        private readonly DossierService $dossierService,
        private readonly FileProcessor $fileProcessor,
    ) {
    }

    public function __invoke(ProcessDocumentCommand $message): void
    {
        $dossier = $this->wooDecisionRepository->find($message->getDossierUuid());
        if (! $dossier) {
            $this->logger->warning('No dossier found for this message', [
                'dossier_uuid' => $message->getDossierUuid(),
            ]);

            return;
        }

        if ($message->isChunked()) {
            // Stitch file together first if needed
            $localFile = $this->assembleChunks(
                $message->getChunkUuid(),
                $message->getChunkCount(),
                $message->getOriginalFilename(),
            );
            if (! $localFile) {
                $this->logger->error('Could not assemble chunks', [
                    'dossier_uuid' => $message->getDossierUuid(),
                    'chunk_uuid' => $message->getChunkUuid(),
                    'chunk_count' => $message->getChunkCount(),
                ]);

                $this->uploadQueue->remove($dossier, $message->getOriginalFilename());

                return;
            }

            $this->fileProcessor->process($localFile, $dossier);
            unlink($localFile->getPathname());

            $this->updateUploadQueueAndDossierCompletion($dossier, $message);

            return;
        }

        // Unchunked file handling
        $localFilePath = $this->entityStorageService->download($message->getRemotePath());
        if (! $localFilePath) {
            $this->logger->error('File could not be downloaded', [
                'dossier_uuid' => $message->getDossierUuid(),
                'file_path' => $message->getRemotePath(),
            ]);

            $this->updateUploadQueueAndDossierCompletion($dossier, $message);

            return;
        }

        $localFile = new UploadedFile($localFilePath, $message->getOriginalFilename());
        try {
            $this->fileProcessor->process($localFile, $dossier);
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            $this->entityStorageService->removeDownload($localFilePath, true);

            $this->updateUploadQueueAndDossierCompletion($dossier, $message);
        }
    }

    public function updateUploadQueueAndDossierCompletion(WooDecision $dossier, ProcessDocumentCommand $message): void
    {
        $this->uploadQueue->remove($dossier, $message->getOriginalFilename());
        $this->dossierService->validateCompletion($dossier);
    }
}
