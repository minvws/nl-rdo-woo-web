<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\GenerateSanitizedInventoryMessage;
use App\Repository\DocumentRepository;
use App\Repository\DossierRepository;
use App\Service\Inventory\Sanitizer\DossierInventoryDataProvider;
use App\Service\Inventory\Sanitizer\InventorySanitizer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GenerateSanitizedInventoryHandler
{
    public function __construct(
        private readonly DossierRepository $dossierRepository,
        private readonly DocumentRepository $documentRepository,
        private readonly LoggerInterface $logger,
        private readonly InventorySanitizer $inventorySanitizer,
    ) {
    }

    public function __invoke(GenerateSanitizedInventoryMessage $message): void
    {
        try {
            $dossier = $this->dossierRepository->find($message->getUuid());
            if (! $dossier) {
                // No dossier found for this message
                $this->logger->warning('No dossier found for this message', [
                    'uuid' => $message->getUuid(),
                ]);

                return;
            }

            $dataProvider = new DossierInventoryDataProvider(
                $dossier,
                $this->documentRepository->getAllDossierDocumentsWithDossiers($dossier)
            );

            $this->inventorySanitizer->generateSanitizedInventory($dataProvider);
        } catch (\Exception $exception) {
            $this->logger->error('Failed to generate sanitized inventory for dossier', [
                'id' => $message->getUuid(),
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
