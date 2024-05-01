<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Ingest;

use App\Domain\Ingest\Covenant\CovenantIngester;
use App\Domain\Ingest\DossierIngester;
use App\Domain\Ingest\IngestException;
use App\Domain\Ingest\WooDecision\WooDecisionIngester;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class DossierIngesterTest extends MockeryTestCase
{
    private CovenantIngester&MockInterface $covenantIngester;
    private WooDecisionIngester&MockInterface $wooDecisionIngester;
    private DossierIngester $ingester;

    public function setUp(): void
    {
        $this->covenantIngester = \Mockery::mock(CovenantIngester::class);
        $this->wooDecisionIngester = \Mockery::mock(WooDecisionIngester::class);

        $this->ingester = new DossierIngester(
            $this->covenantIngester,
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

    public function testCovenantIsForwarded(): void
    {
        $refresh = true;
        $dossier = \Mockery::mock(Covenant::class);

        $this->covenantIngester->expects('ingest')->with($dossier, $refresh);

        $this->ingester->ingest($dossier, $refresh);
    }

    public function testExceptionIsThrownForUnsupportedType(): void
    {
        $refresh = true;
        $dossier = \Mockery::mock(AbstractDossier::class);
        $dossier->shouldReceive('getType')->andReturn(DossierType::COVENANT);

        $this->expectExceptionObject(IngestException::forUnsupportedDossierType(DossierType::COVENANT));

        $this->ingester->ingest($dossier, $refresh);
    }
}
