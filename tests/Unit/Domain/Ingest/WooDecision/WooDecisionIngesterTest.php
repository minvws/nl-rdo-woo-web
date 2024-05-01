<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Ingest\WooDecision;

use App\Domain\Ingest\WooDecision\WooDecisionIngester;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Search\Index\IndexDossierMessage;
use App\Entity\Document;
use App\Message\IngestDecisionMessage;
use App\Service\Ingest\IngestService;
use App\Service\Ingest\Options;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class WooDecisionIngesterTest extends MockeryTestCase
{
    private MessageBusInterface&MockInterface $messageBus;
    private WooDecisionIngester $ingester;
    private IngestService&MockInterface $ingestService;

    public function setUp(): void
    {
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);
        $this->ingestService = \Mockery::mock(IngestService::class);

        $this->ingester = new WooDecisionIngester(
            $this->ingestService,
            $this->messageBus,
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

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (IndexDossierMessage $message) use ($dossierId) {
                self::assertEquals($dossierId, $message->getUuid());

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (IngestDecisionMessage $message) use ($dossierId) {
                self::assertEquals($dossierId, $message->getUuid());

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->ingestService->expects('ingest')->with($docA, \Mockery::on(
            static fn (Options $options) => $options->forceRefresh() === false)
        );

        $this->ingestService->expects('ingest')->with($docB, \Mockery::on(
            static fn (Options $options) => $options->forceRefresh() === false)
        );

        $this->ingester->ingest($dossier, false);
    }
}
