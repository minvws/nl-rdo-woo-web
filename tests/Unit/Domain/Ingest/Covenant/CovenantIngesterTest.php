<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Ingest\Covenant;

use App\Domain\Ingest\Covenant\CovenantIngester;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Search\Index\IndexDossierMessage;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class CovenantIngesterTest extends MockeryTestCase
{
    private MessageBusInterface&MockInterface $messageBus;
    private CovenantIngester $ingester;

    public function setUp(): void
    {
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);

        $this->ingester = new CovenantIngester(
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
