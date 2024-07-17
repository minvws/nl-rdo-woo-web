<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Ingest\Dossier\Strategy;

use App\Domain\Ingest\Dossier\Strategy\DefaultDossierIngestStrategy;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument;
use App\Domain\Search\Index\Dossier\IndexDossierCommand;
use App\Domain\Search\Index\SubType\IndexAttachmentCommand;
use App\Domain\Search\Index\SubType\IndexMainDocumentCommand;
use Doctrine\Common\Collections\ArrayCollection;
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
        $mainDocumentId = Uuid::v6();
        $mainDocument = \Mockery::mock(CovenantDocument::class);
        $mainDocument->shouldReceive('getId')->andReturn($mainDocumentId);

        $attachmentId = Uuid::v6();
        $attachment = \Mockery::mock(CovenantAttachment::class);
        $attachment->shouldReceive('getId')->andReturn($attachmentId);

        $dossierId = Uuid::v6();
        $dossier = \Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getId')->andReturn($dossierId);
        $dossier->shouldReceive('getAttachments')->andReturn(new ArrayCollection([$attachment]));
        $dossier->shouldReceive('getDocument')->andReturn($mainDocument);

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (IndexDossierCommand $message) use ($dossierId) {
                self::assertEquals($dossierId, $message->getUuid());

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (IndexMainDocumentCommand $message) use ($mainDocumentId) {
                self::assertEquals($mainDocumentId, $message->uuid);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (IndexAttachmentCommand $message) use ($attachmentId) {
                self::assertEquals($attachmentId, $message->uuid);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->ingester->ingest($dossier, false);
    }
}
