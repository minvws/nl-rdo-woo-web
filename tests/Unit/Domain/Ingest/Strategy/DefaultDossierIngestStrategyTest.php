<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Ingest\Strategy;

use App\Domain\Ingest\Stategy\DefaultDossierIngestStrategy;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Search\Index\IndexDossierMessage;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class DefaultDossierIngestStrategyTest extends MockeryTestCase
{
    private MessageBusInterface&MockInterface $messageBus;
    private DefaultDossierIngestStrategy $ingester;

    public function setUp(): void
    {
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);

        $this->ingester = new DefaultDossierIngestStrategy(
            $this->messageBus,
        );
    }

    public function testIngest(): void
    {
        $dossierId = Uuid::v6();
        $dossier = \Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getId')->andReturn($dossierId);

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (IndexDossierMessage $message) use ($dossierId) {
                self::assertEquals($dossierId, $message->getUuid());

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->ingester->ingest($dossier, false);
    }
}
