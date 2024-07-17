<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Ingest\Dossier\Strategy;

use App\Domain\Ingest\Dossier\Strategy\DefaultDossierIngestStrategy;
use App\Domain\Ingest\Dossier\Strategy\WooDecisionIngestStrategy;
use App\Domain\Ingest\IngestOptions;
use App\Domain\Ingest\SubType\SubTypeIngester;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Entity\Document;
use App\Message\IngestDecisionMessage;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class WooDecisionIngestStrategyTest extends MockeryTestCase
{
    private MessageBusInterface&MockInterface $messageBus;
    private WooDecisionIngestStrategy $ingester;
    private SubTypeIngester&MockInterface $ingestService;
    private DefaultDossierIngestStrategy&MockInterface $defaultIngestStrategy;

    public function setUp(): void
    {
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);
        $this->ingestService = \Mockery::mock(SubTypeIngester::class);
        $this->defaultIngestStrategy = \Mockery::mock(DefaultDossierIngestStrategy::class);

        $this->ingester = new WooDecisionIngestStrategy(
            $this->ingestService,
            $this->messageBus,
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

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (IngestDecisionMessage $message) use ($dossierId) {
                self::assertEquals($dossierId, $message->getUuid());

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->ingestService->expects('ingest')->with($docA, \Mockery::on(
            static fn (IngestOptions $options) => $options->forceRefresh() === false
        ));

        $this->ingestService->expects('ingest')->with($docB, \Mockery::on(
            static fn (IngestOptions $options) => $options->forceRefresh() === false
        ));

        $this->ingester->ingest($dossier, false);
    }
}
