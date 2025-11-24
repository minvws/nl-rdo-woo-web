<?php

declare(strict_types=1);

namespace Shared\Domain\Ingest\Process\Dossier\Strategy;

use Shared\Domain\Ingest\Process\Dossier\DossierIngestStrategyInterface;
use Shared\Domain\Ingest\Process\IngestProcessOptions;
use Shared\Domain\Ingest\Process\SubType\SubTypeIngester;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Webmozart\Assert\Assert;

readonly class WooDecisionIngestStrategy implements DossierIngestStrategyInterface
{
    public function __construct(
        private SubTypeIngester $ingester,
        private DefaultDossierIngestStrategy $defaultDossierIngester,
    ) {
    }

    public function ingest(AbstractDossier $dossier, bool $refresh): void
    {
        /** @var WooDecision $dossier */
        Assert::isInstanceOf($dossier, WooDecision::class);

        $this->defaultDossierIngester->ingest($dossier, $refresh);

        $options = new IngestProcessOptions(forceRefresh: $refresh);
        foreach ($dossier->getDocuments() as $document) {
            $this->ingester->ingest($document, $options);
        }
    }
}
