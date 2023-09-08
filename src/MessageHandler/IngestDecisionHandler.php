<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\IngestDecisionMessage;
use App\Repository\DossierRepository;
use App\Service\Worker\Pdf\Extractor\DecisionContentExtractor;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Ingest a dossier decision file into the system.
 */
#[AsMessageHandler]
class IngestDecisionHandler
{
    public function __construct(
        private readonly DossierRepository $dossierRepository,
        private readonly DecisionContentExtractor $contentExtractor,
    ) {
    }

    public function __invoke(IngestDecisionMessage $message): void
    {
        $dossier = $this->dossierRepository->find($message->getUuid());
        if (! $dossier) {
            throw new \RuntimeException('Cannot find dossier with UUID ' . $message->getUuid());
        }

        $decision = $dossier->getDecisionDocument();
        if (! $decision) {
            throw new \RuntimeException('No decision entity in dossier with UUID ' . $message->getUuid());
        }

        if (! $decision->getFileInfo()->isUploaded()) {
            throw new \RuntimeException('Cannot ingest missing decision file for dossier with UUID ' . $message->getUuid());
        }

        $this->contentExtractor->extract($dossier, $decision, $message->getRefresh());
    }
}
