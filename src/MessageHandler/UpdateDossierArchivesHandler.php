<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\UpdateDossierArchivesMessage;
use App\Repository\WooDecisionRepository;
use App\Service\BatchDownloadService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateDossierArchivesHandler
{
    public function __construct(
        private readonly BatchDownloadService $batchDownloadService,
        private readonly WooDecisionRepository $wooDecisionRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(UpdateDossierArchivesMessage $message): void
    {
        $dossier = $this->wooDecisionRepository->find($message->getUuid());
        if (! $dossier) {
            // No dossier found for this message
            $this->logger->warning('No dossier found for this message', [
                'uuid' => $message->getUuid(),
            ]);

            return;
        }

        $this->batchDownloadService->refreshForEntity($dossier);
    }
}
