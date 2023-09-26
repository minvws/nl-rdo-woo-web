<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Message\ReplaceDocumentMessage;
use App\Service\FileProcessService;
use App\Service\Ingest\IngestService;
use App\Service\Ingest\Options;
use App\Service\Storage\DocumentStorageService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Replace a file for a specific document.
 */
#[AsMessageHandler]
class ReplaceDocumentHandler
{
    public function __construct(
        private readonly FileProcessService $fileProcessService,
        private readonly DocumentStorageService $storageService,
        private readonly EntityManagerInterface $doctrine,
        private readonly LoggerInterface $logger,
        private readonly IngestService $ingester,
    ) {
    }

    public function __invoke(ReplaceDocumentMessage $message): void
    {
        $dossier = $this->doctrine->getRepository(Dossier::class)->find($message->getDossierUuid());
        if (! $dossier) {
            $this->logger->warning('No dossier found for this message', [
                'dossier_uuid' => $message->getDossierUuid(),
            ]);

            return;
        }

        $document = $this->doctrine->getRepository(Document::class)->find($message->getDocumentUuid());
        if (! $document) {
            $this->logger->warning('No document found for this message', [
                'document_uuid' => $message->getDocumentUuid(),
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

                return;
            }

            $this->fileProcessService->processFileForDocument($localFile, $dossier, $document, $message->getOriginalFilename(), 'pdf');
            unlink($localFile->getPathname());

            $this->handleIngest($document);

            return;
        }

        // Unchunked file handling
        $localFilePath = $this->storageService->download($message->getRemotePath());
        if (! $localFilePath) {
            $this->logger->error('File could not be downloaded', [
                'dossier_uuid' => $message->getDossierUuid(),
                'file_path' => $message->getRemotePath(),
            ]);

            return;
        }

        $localFile = new \SplFileObject($localFilePath);
        try {
            $this->fileProcessService->processFileForDocument($localFile, $dossier, $document, $message->getOriginalFilename(), 'pdf');
            $this->handleIngest($document);
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            $this->storageService->removeDownload($localFilePath, true);
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

    private function handleIngest(Document $document): void
    {
        if ($document->isWithdrawn()) {
            return;
        }

        $options = new Options();
        $options->setForceRefresh(true);

        $this->ingester->ingest($document, $options);
    }
}
