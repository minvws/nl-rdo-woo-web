<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Domain\Upload\Process\FileProcessor;
use App\Domain\Upload\UploadedFile;
use App\Entity\Dossier;
use App\Message\ProcessDocumentMessage;
use App\Service\DocumentUploadQueue;
use App\Service\DossierService;
use App\Service\Storage\EntityStorageService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Process a document (archive, pdf) that is uploaded to the system. If the upload has been chunked, it will be stitched together first.
 */
#[AsMessageHandler]
class ProcessDocumentHandler
{
    public function __construct(
        private readonly EntityStorageService $entityStorageService,
        private readonly EntityManagerInterface $doctrine,
        private readonly LoggerInterface $logger,
        private readonly DocumentUploadQueue $uploadQueue,
        private readonly DossierService $dossierService,
        private readonly FileProcessor $fileProcessor,
    ) {
    }

    public function __invoke(ProcessDocumentMessage $message): void
    {
        $dossier = $this->doctrine->getRepository(Dossier::class)->find($message->getDossierUuid());
        if (! $dossier) {
            // No dossier found for this message
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

    protected function assembleChunks(string $chunkUuid, int $chunkCount, string $originalFilename): ?UploadedFile
    {
        $path = sprintf('%s/assembled-%s', sys_get_temp_dir(), $chunkUuid);
        $stitchedFile = new \SplFileObject($path, 'w');

        for ($i = 0; $i < $chunkCount; $i++) {
            // Check if the chunk exists
            $remoteChunkPath = '/uploads/chunks/' . $chunkUuid . '/' . $i;

            $localChunkFile = $this->entityStorageService->download($remoteChunkPath);
            if (! $localChunkFile) {
                $this->logger->error('Chunk is not readable', [
                    'uuid' => $chunkUuid,
                    'chunk' => $i,
                ]);

                return null;
            }

            // Add chunk to the stitched file
            $chunk = new \SplFileObject($localChunkFile);
            $chunk->rewind();
            while (! $chunk->eof()) {
                $data = $chunk->fread(1024 * 64);
                if ($data === false) {
                    continue;
                }
                $stitchedFile->fwrite($data);
            }

            // Unlink chunk, as we don't need it anymore
            $this->entityStorageService->removeDownload($localChunkFile);
            $chunk = null;
        }

        return UploadedFile::fromSplFile($stitchedFile->getFileInfo(), $originalFilename);
    }

    public function updateUploadQueueAndDossierCompletion(Dossier $dossier, ProcessDocumentMessage $message): void
    {
        $this->uploadQueue->remove($dossier, $message->getOriginalFilename());
        $this->dossierService->validateCompletion($dossier);
    }
}
