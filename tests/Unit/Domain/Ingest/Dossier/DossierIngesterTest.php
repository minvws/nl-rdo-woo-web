<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Ingest\Dossier;

use App\Domain\Ingest\Dossier\DossierIngester;
use App\Domain\Ingest\Dossier\Strategy\DefaultDossierIngestStrategy;
use App\Domain\Ingest\Dossier\Strategy\WooDecisionIngestStrategy;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class DossierIngesterTest extends MockeryTestCase
{
    private DefaultDossierIngestStrategy&MockInterface $defaultIngester;
    private WooDecisionIngestStrategy&MockInterface $wooDecisionIngester;
    private DossierIngester $ingester;

    public function setUp(): void
    {
        $this->defaultIngester = \Mockery::mock(DefaultDossierIngestStrategy::class);
        $this->wooDecisionIngester = \Mockery::mock(WooDecisionIngestStrategy::class);

        $this->ingester = new DossierIngester(
            $this->defaultIngester,
            $this->wooDecisionIngester
        );
    }

    public function testWooDecisionIsForwarded(): void
    {
        $refresh = true;
        $dossier = \Mockery::mock(WooDecision::class);

        $this->wooDecisionIngester->expects('ingest')->with($dossier, $refresh);

        $this->ingester->ingest($dossier, $refresh);
    }

    public function testCovenantIsForwardedToDefaultStrategy(): void
    {
        $refresh = true;
        $dossier = \Mockery::mock(Covenant::class);

        $this->defaultIngester->expects('ingest')->with($dossier, $refresh);

        $this->ingester->ingest($dossier, $refresh);
    }
}
