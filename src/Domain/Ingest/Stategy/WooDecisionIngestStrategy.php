<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Stategy;

use App\Domain\Ingest\DossierIngestStrategyInterface;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Message\IngestDecisionMessage;
use App\Service\Ingest\IngestService;
use App\Service\Ingest\Options;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

readonly class WooDecisionIngestStrategy implements DossierIngestStrategyInterface
{
    public function __construct(
        private IngestService $ingester,
        private MessageBusInterface $messageBus,
        private DefaultDossierIngestStrategy $defaultDossierIngester,
    ) {
    }

    public function ingest(AbstractDossier $dossier, bool $refresh): void
    {
        /** @var WooDecision $dossier */
        Assert::isInstanceOf($dossier, WooDecision::class);

        $this->defaultDossierIngester->ingest($dossier, $refresh);

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
