<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Handler;

use App\Domain\Publication\Dossier\Type\WooDecision\Command\RemoveInventoryAndDocumentsCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Repository\WooDecisionRepository;
use App\Service\BatchDownloadService;
use App\Service\DocumentService;
use App\Service\DossierService;
use App\Service\Inventory\InventoryService;
use Psr\Log\LoggerInterface;
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
                $this->batchDownloadService->refreshForEntity($dossier);
            }

            $this->dossierService->validateCompletion($dossier);
        } catch (\Exception $e) {
            $this->logger->error('Failed to remove dossier inventory and documents', [
                'id' => $message->getUuid(),
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
