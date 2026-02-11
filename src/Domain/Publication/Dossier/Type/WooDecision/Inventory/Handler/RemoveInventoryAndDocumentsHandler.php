<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\Inventory\Handler;

use Exception;
use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\BatchDownload\BatchDownloadScope;
use Shared\Domain\Publication\BatchDownload\BatchDownloadService;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inventory\Command\RemoveInventoryAndDocumentsCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Service\DocumentService;
use Shared\Service\DossierService;
use Shared\Service\Inventory\InventoryService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RemoveInventoryAndDocumentsHandler
{
    public function __construct(
        private readonly WooDecisionRepository $wooDecisionRepository,
        private readonly InventoryService $inventoryService,
        private readonly LoggerInterface $logger,
        private readonly BatchDownloadService $batchDownloadService,
        private readonly DocumentService $documentService,
        private readonly DossierService $dossierService,
    ) {
    }

    public function __invoke(RemoveInventoryAndDocumentsCommand $message): void
    {
        try {
            $dossier = $this->wooDecisionRepository->find($message->getUuid());
            if (! $dossier) {
                $this->logger->warning('No dossier found for this message', [
                    'uuid' => $message->getUuid(),
                ]);

                return;
            }

            $removedInventory = $this->inventoryService->removeInventories($dossier);

            $removedDocuments = false;
            foreach ($dossier->getDocuments() as $document) {
                $this->documentService->removeDocumentFromDossier($dossier, $document);
                $removedDocuments = true;
            }

            if ($removedInventory || $removedDocuments) {
                $this->batchDownloadService->refresh(
                    BatchDownloadScope::forWooDecision($dossier),
                );
            }

            $this->dossierService->validateCompletion($dossier);
        } catch (Exception $e) {
            $this->logger->error('Failed to remove dossier inventory and documents', [
                'id' => $message->getUuid(),
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
