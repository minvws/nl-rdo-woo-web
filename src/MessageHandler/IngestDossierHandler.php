<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Dossier;
use App\Message\IngestDecisionMessage;
use App\Message\IngestDossierMessage;
use App\Service\Elastic\ElasticService;
use App\Service\Ingest\IngestLogger;
use App\Service\Ingest\IngestService;
use App\Service\Ingest\Options;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Ingest a dossier into the system.
 */
#[AsMessageHandler]
class IngestDossierHandler
{
    public function __construct(
        private readonly ElasticService $elasticService,
        private readonly EntityManagerInterface $doctrine,
        private readonly IngestService $ingester,
        private readonly IngestLogger $ingestLogger,
        private readonly MessageBusInterface $bus,
    ) {
    }

    public function __invoke(IngestDossierMessage $message): void
    {
        $dossier = $this->doctrine->getRepository(Dossier::class)->find($message->getUuid());
        if (! $dossier) {
            throw new \RuntimeException('Cannot find dossier with UUID ' . $message->getUuid());
        }

        $this->elasticService->updateDossier($dossier, false);

        $this->ingestLogger->setFlush(false);

        $options = new Options();
        $options->setForceRefresh($message->getRefresh());

        foreach ($dossier->getDocuments() as $document) {
            $this->ingester->ingest($document, $options);
        }

        $this->doctrine->flush();

        if ($dossier->getId()) {
            $this->bus->dispatch(
                new IngestDecisionMessage($dossier->getId(), false)
            );
        }
    }
}
