<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Process\Dossier\Strategy;

use App\Domain\Ingest\Process\Dossier\DossierIngestStrategyInterface;
use App\Domain\Ingest\Process\IngestProcessOptions;
use App\Domain\Ingest\Process\SubType\SubTypeIngester;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
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

        $options = new IngestProcessOptions();
        $options->setForceRefresh($refresh);
        foreach ($dossier->getDocuments() as $document) {
            $this->ingester->ingest($document, $options);
        }
    }
}
