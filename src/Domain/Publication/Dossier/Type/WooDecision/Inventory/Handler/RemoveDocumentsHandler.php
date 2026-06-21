<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\Inventory\Handler;

use Exception;
use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\BatchDownload\BatchDownloadScope;
use Shared\Domain\Publication\BatchDownload\BatchDownloadService;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inventory\Command\RemoveDocumentsCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Service\DocumentService;
use Shared\Service\DossierService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RemoveDocumentsHandler
{
    public function __construct(
        private readonly WooDecisionRepository $wooDecisionRepository,
        private readonly LoggerInterface $logger,
        private readonly BatchDownloadService $batchDownloadService,
        private readonly DocumentService $documentService,
        private readonly DossierService $dossierService,
    ) {
    }

    public function __invoke(RemoveDocumentsCommand $message): void
    {
        try {
            $dossier = $this->wooDecisionRepository->find($message->getUuid());
            if (! $dossier) {
                $this->logger->warning('No dossier found for this message', [
                    'uuid' => $message->getUuid(),
                ]);

                return;
            }

            if (! $dossier->canRemoveInventory()) {
                $this->logger->warning('Cannot remove documents: the inventory must be optional and the dossier status must be concept.', [
                    'uuid' => $message->getUuid(),
                    'isOptional' => $dossier->isInventoryOptional(),
                    'status' => $dossier->getStatus(),
                ]);

                return;
            }

            $removedDocuments = false;
            foreach ($dossier->getDocuments() as $document) {
                $this->documentService->removeDocumentFromDossier($dossier, $document);
                $removedDocuments = true;
            }

            if ($removedDocuments) {
                $this->batchDownloadService->refresh(
                    BatchDownloadScope::forWooDecision($dossier),
                );
            }

            $this->dossierService->validateCompletion($dossier);
        } catch (Exception $e) {
            $this->logger->error('Failed to remove dossier documents', [
                'id' => $message->getUuid(),
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
