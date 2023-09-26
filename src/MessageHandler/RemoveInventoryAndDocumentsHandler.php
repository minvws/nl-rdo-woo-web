<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Dossier;
use App\Message\RemoveInventoryAndDocumentsMessage;
use App\Service\ArchiveService;
use App\Service\DocumentService;
use App\Service\DossierService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RemoveInventoryAndDocumentsHandler
{
    public function __construct(
        private readonly EntityManagerInterface $doctrine,
        private readonly DossierService $dossierService,
        private readonly LoggerInterface $logger,
        private readonly ArchiveService $archiveService,
        private readonly DocumentService $documentService,
    ) {
    }

    public function __invoke(RemoveInventoryAndDocumentsMessage $message): void
    {
        try {
            $dossier = $this->doctrine->getRepository(Dossier::class)->find($message->getUuid());
            if (! $dossier) {
                // No dossier found for this message
                $this->logger->warning('No dossier found for this message', [
                    'uuid' => $message->getUuid(),
                ]);

                return;
            }

            $removedInventory = $this->dossierService->removeInventories($dossier);

            $removedDocuments = false;
            foreach ($dossier->getDocuments() as $document) {
                $this->documentService->removeDocumentFromDossier($dossier, $document);
                $removedDocuments = true;
            }

            if ($removedInventory || $removedDocuments) {
                $this->archiveService->deleteDossierArchives($dossier);
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to remove dossier inventory and documents', [
                'id' => $message->getUuid(),
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
