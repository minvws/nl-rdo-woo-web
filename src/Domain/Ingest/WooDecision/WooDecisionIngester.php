<?php

declare(strict_types=1);

namespace App\Domain\Ingest\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Search\Index\IndexDossierMessage;
use App\Message\IngestDecisionMessage;
use App\Service\Ingest\IngestService;
use App\Service\Ingest\Options;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class WooDecisionIngester
{
    public function __construct(
        private IngestService $ingester,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function ingest(WooDecision $dossier, bool $refresh): void
    {
        $this->messageBus->dispatch(
            new IndexDossierMessage($dossier->getId(), $refresh)
        );

        $this->messageBus->dispatch(
            IngestDecisionMessage::forDossier($dossier)
        );

        $options = new Options();
        $options->setForceRefresh($refresh);
        foreach ($dossier->getDocuments() as $document) {
            $this->ingester->ingest($document, $options);
        }
    }
}
