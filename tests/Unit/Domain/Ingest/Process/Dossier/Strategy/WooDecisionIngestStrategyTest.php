<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Ingest\Process\Dossier\Strategy;

use App\Domain\Ingest\Process\Dossier\Strategy\DefaultDossierIngestStrategy;
use App\Domain\Ingest\Process\Dossier\Strategy\WooDecisionIngestStrategy;
use App\Domain\Ingest\Process\IngestProcessOptions;
use App\Domain\Ingest\Process\SubType\SubTypeIngester;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

class WooDecisionIngestStrategyTest extends MockeryTestCase
{
    private WooDecisionIngestStrategy $ingester;
    private SubTypeIngester&MockInterface $ingestService;
    private DefaultDossierIngestStrategy&MockInterface $defaultIngestStrategy;

    protected function setUp(): void
    {
        $this->ingestService = \Mockery::mock(SubTypeIngester::class);
        $this->defaultIngestStrategy = \Mockery::mock(DefaultDossierIngestStrategy::class);

        $this->ingester = new WooDecisionIngestStrategy(
            $this->ingestService,
            $this->defaultIngestStrategy,
        );
    }

    public function testIngest(): void
    {
        $docA = \Mockery::mock(Document::class);
        $docB = \Mockery::mock(Document::class);

        $dossierId = Uuid::v6();
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getId')->andReturn($dossierId);
        $dossier->shouldReceive('getDocuments')->andReturn(new ArrayCollection([
            $docA,
            $docB,
        ]));

        $this->defaultIngestStrategy->expects('ingest')->with($dossier, false);

        $this->ingestService->expects('ingest')->with($docA, \Mockery::on(
            static fn (IngestProcessOptions $options) => $options->forceRefresh() === false
        ));

        $this->ingestService->expects('ingest')->with($docB, \Mockery::on(
            static fn (IngestProcessOptions $options) => $options->forceRefresh() === false
        ));

        $this->ingester->ingest($dossier, false);
    }
}
