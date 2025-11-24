<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Ingest\Process\Dossier;

use Mockery\MockInterface;
use Shared\Domain\Ingest\Process\Dossier\DossierIngester;
use Shared\Domain\Ingest\Process\Dossier\Strategy\DefaultDossierIngestStrategy;
use Shared\Domain\Ingest\Process\Dossier\Strategy\WooDecisionIngestStrategy;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Tests\Unit\UnitTestCase;

class DossierIngesterTest extends UnitTestCase
{
    private DefaultDossierIngestStrategy&MockInterface $defaultIngester;
    private WooDecisionIngestStrategy&MockInterface $wooDecisionIngester;
    private DossierIngester $ingester;

    protected function setUp(): void
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
