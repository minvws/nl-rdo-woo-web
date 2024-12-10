<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Handler;

use App\Domain\Ingest\Process\IngestProcessOptions;
use App\Domain\Ingest\Process\SubType\SubTypeIngester;
use App\Domain\Publication\Dossier\Type\WooDecision\Command\ReplaceDocumentCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Repository\DocumentRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\Repository\WooDecisionRepository;
use App\Domain\Upload\Process\DocumentFileProcessor;
use App\Domain\Upload\UploadedFile;
use App\Service\Storage\EntityStorageService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Replace a file for a specific document.
 *
 * @codeCoverageIgnore Currently hard to test due to chunked file handling, which will be removed in woo-3346
 */
#[AsMessageHandler]
readonly class ReplaceDocumentHandler
{
    use AssembleChunksTrait;

    public function __construct(
        private EntityStorageService $entityStorageService,
        private WooDecisionRepository $wooDecisionRepository,
        private DocumentRepository $documentRepository,
        private LoggerInterface $logger,
        private SubTypeIngester $ingester,
        private DocumentFileProcessor $documentFileProcessor,
    ) {
    }

    public function __invoke(ReplaceDocumentCommand $message): void
    {
        $dossier = $this->wooDecisionRepository->find($message->getDossierUuid());
        if (! $dossier) {
            $this->logger->warning('No dossier found for this message', [
                'dossier_uuid' => $message->getDossierUuid(),
            ]);

            return;
        }

        $document = $this->documentRepository->find($message->getDocumentUuid());
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

            $this->documentFileProcessor->process($localFile, $dossier, $document);
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
            $this->documentFileProcessor->process($localFile, $dossier, $document);
            $this->handleIngest($document);
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            $this->entityStorageService->removeDownload($localFilePath, true);
        }
    }

    private function handleIngest(Document $document): void
    {
        if (! $document->shouldBeUploaded()) {
            return;
        }

        $this->ingester->ingest($document, new IngestProcessOptions(forceRefresh: true));
    }
}
