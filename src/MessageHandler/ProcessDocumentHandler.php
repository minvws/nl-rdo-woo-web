<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Dossier;
use App\Message\ProcessDocumentMessage;
use App\Service\DocumentUploadQueue;
use App\Service\FileProcessService;
use App\Service\Storage\DocumentStorageService;
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
        private readonly FileProcessService $fileProcessService,
        private readonly DocumentStorageService $storageService,
        private readonly EntityManagerInterface $doctrine,
        private readonly LoggerInterface $logger,
        private readonly DocumentUploadQueue $uploadQueue,
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
            $localFile = $this->assembleChunks($message->getChunkUuid(), $message->getChunkCount());
            if (! $localFile) {
                $this->logger->error('Could not assemble chunks', [
                    'dossier_uuid' => $message->getDossierUuid(),
                    'chunk_uuid' => $message->getChunkUuid(),
                    'chunk_count' => $message->getChunkCount(),
                ]);

                $this->uploadQueue->remove($dossier, $message->getOriginalFilename());

                return;
            }

            $this->fileProcessService->processFile($localFile, $dossier, $message->getOriginalFilename());
            unlink($localFile->getPathname());

            $this->uploadQueue->remove($dossier, $message->getOriginalFilename());

            return;
        }

        // Unchunked file handling
        $localFilePath = $this->storageService->download($message->getRemotePath());
        if (! $localFilePath) {
            $this->logger->error('File could not be downloaded', [
                'dossier_uuid' => $message->getDossierUuid(),
                'file_path' => $message->getRemotePath(),
            ]);

            $this->uploadQueue->remove($dossier, $message->getOriginalFilename());

            return;
        }

        $localFile = new \SplFileObject($localFilePath);
        try {
            $this->fileProcessService->processFile($localFile, $dossier, $message->getOriginalFilename());
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            $this->storageService->removeDownload($localFilePath, true);

            $this->uploadQueue->remove($dossier, $message->getOriginalFilename());
        }
    }

    protected function assembleChunks(string $chunkUuid, int $chunkCount): ?\SplFileInfo
    {
        $path = sprintf('%s/assembled-%s', sys_get_temp_dir(), $chunkUuid);
        $stitchedFile = new \SplFileObject($path, 'w');

        for ($i = 0; $i < $chunkCount; $i++) {
            // Check if the chunk exists
            $remoteChunkPath = '/uploads/chunks/' . $chunkUuid . '/' . $i;

            $localChunkFile = $this->storageService->download($remoteChunkPath);
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
            $this->storageService->removeDownload($localChunkFile);
            $chunk = null;
        }

        return $stitchedFile->getFileInfo();
    }
}
