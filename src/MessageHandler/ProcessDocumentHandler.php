<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Dossier;
use App\Message\ProcessDocumentMessage;
use App\Service\DocumentService;
use App\Service\Storage\DocumentStorageService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ProcessDocumentHandler
{
    protected EntityManagerInterface $doctrine;
    protected LoggerInterface $logger;
    protected DocumentService $documentService;
    protected DocumentStorageService $storageService;

    public function __construct(
        DocumentService $documentService,
        DocumentStorageService $storageService,
        EntityManagerInterface $doctrine,
        LoggerInterface $logger
    ) {
        $this->documentService = $documentService;
        $this->storageService = $storageService;
        $this->doctrine = $doctrine;
        $this->logger = $logger;
    }

    public function __invoke(ProcessDocumentMessage $message): void
    {
        $dossier = $this->doctrine->getRepository(Dossier::class)->find($message->getUuid());
        if (! $dossier) {
            // No dossier found for this message
            $this->logger->warning('No dossier found for this message', [
                'dossier_uuid' => $message->getUuid(),
            ]);

            return;
        }

        if ($message->isChunked()) {
            // Stitch file together first if needed
            $localFile = $this->assembleChunks($message->getChunkUuid(), $message->getChunkCount());
            if (! $localFile) {
                $this->logger->error('Could not assemble chunks', [
                    'dossier_uuid' => $message->getUuid(),
                    'chunk_uuid' => $message->getChunkUuid(),
                    'chunk_count' => $message->getChunkCount(),
                ]);

                return;
            }

            $this->documentService->processDocument($localFile, $dossier, $message->getOriginalFilename());
            unlink($localFile->getPathname());

            return;
        }

        // Unchunked file handling
        $localFilePath = $this->storageService->download($message->getRemotePath());
        if (! $localFilePath) {
            $this->logger->error('File could not be downloaded', [
                'dossier_uuid' => $message->getUuid(),
                'file_path' => $message->getRemotePath(),
            ]);

            return;
        }

        $localFile = new \SplFileObject($localFilePath);
        $this->documentService->processDocument($localFile, $dossier, $message->getOriginalFilename());
        $this->storageService->removeDownload($localFilePath);
    }

    protected function assembleChunks(string $uuid, int $chunkCount): ?\SplFileInfo
    {
        $path = sprintf('%s/assembled-%s', sys_get_temp_dir(), $uuid);
        $stitchedFile = new \SplFileObject($path, 'w');

        for ($i = 0; $i < $chunkCount; $i++) {
            // Check if the chunk exists
            $remoteChunkPath = '/uploads/chunks/' . $uuid . '/' . $i;

            $localChunkFile = $this->storageService->download($remoteChunkPath);
            if (! $localChunkFile) {
                $this->logger->error('Chunk is not readable', [
                    'uuid' => $uuid,
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
