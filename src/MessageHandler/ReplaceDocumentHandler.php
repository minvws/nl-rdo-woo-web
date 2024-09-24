<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Domain\Ingest\Process\IngestProcessOptions;
use App\Domain\Ingest\Process\SubType\SubTypeIngester;
use App\Domain\Upload\Process\DocumentFileProcessor;
use App\Domain\Upload\UploadedFile;
use App\Entity\Document;
use App\Entity\Dossier;
use App\Message\ReplaceDocumentMessage;
use App\Service\Storage\EntityStorageService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Replace a file for a specific document.
 */
#[AsMessageHandler]
readonly class ReplaceDocumentHandler
{
    public function __construct(
        private EntityStorageService $entityStorageService,
        private EntityManagerInterface $doctrine,
        private LoggerInterface $logger,
        private SubTypeIngester $ingester,
        private DocumentFileProcessor $documentFileProcessor,
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

                return;
            }

            $this->documentFileProcessor->process($localFile, $dossier, $document, 'pdf');
            unlink($localFile->getPathname());

            $this->handleIngest($document);

            return;
        }

        // Unchunked file handling
        $localFilePath = $this->entityStorageService->download($message->getRemotePath());
        if (! $localFilePath) {
            $this->logger->error('File could not be downloaded', [
                'dossier_uuid' => $message->getDossierUuid(),
                'file_path' => $message->getRemotePath(),
            ]);

            return;
        }

        $localFile = new UploadedFile($localFilePath, $message->getOriginalFilename());
        try {
            $this->documentFileProcessor->process($localFile, $dossier, $document, 'pdf');
            $this->handleIngest($document);
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            $this->entityStorageService->removeDownload($localFilePath, true);
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

    private function handleIngest(Document $document): void
    {
        if (! $document->shouldBeUploaded()) {
            return;
        }

        $options = new IngestProcessOptions();
        $options->setForceRefresh(true);

        $this->ingester->ingest($document, $options);
    }
}
