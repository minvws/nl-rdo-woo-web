<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Attachment;

use Doctrine\Common\Collections\ArrayCollection;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Attachment\Command\DeleteAttachmentCommand;
use Shared\Domain\Publication\Attachment\Command\DeleteAttachmentWithOverrideCommand;
use Shared\Domain\Publication\Attachment\DossierWithAttachmentDeleteStrategy;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

final class DossierWithAttachmentsDeleteStrategyTest extends UnitTestCase
{
    private MessageBusInterface&MockInterface $messageBus;
    private DossierWithAttachmentDeleteStrategy $strategy;

    protected function setUp(): void
    {
        $this->messageBus = Mockery::mock(MessageBusInterface::class);
        $this->strategy = new DossierWithAttachmentDeleteStrategy($this->messageBus);

        parent::setUp();
    }

    public function testDeleteReturnsEarlyWhenDossierHasNoAttachments(): void
    {
        $this->messageBus->shouldNotHaveBeenCalled();
        $dossier = Mockery::mock(AbstractDossier::class);

        $this->strategy->delete($dossier);
    }

    public function testDeleteAttachments(): void
    {
        $attachmentA = Mockery::mock(CovenantAttachment::class);
        $attachmentA->shouldReceive('getId')->andReturn($attachmentIdA = Uuid::v6());

        $attachmentB = Mockery::mock(CovenantAttachment::class);
        $attachmentB->shouldReceive('getId')->andReturn($attachmentIdB = Uuid::v6());

        $dossier = Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getId')->andReturn($dossierId = Uuid::v6());
        $dossier->shouldReceive('getAttachments')->andReturn(new ArrayCollection([
            $attachmentA,
            $attachmentB,
        ]));

        $this->messageBus->expects('dispatch')->with(Mockery::on(
            static function (DeleteAttachmentCommand $message) use ($attachmentIdA, $dossierId) {
                self::assertEquals($attachmentIdA, $message->attachmentId);
                self::assertEquals($dossierId, $message->dossierId);

                return true;
            }
        ))->andReturns(new Envelope(new stdClass()));

        $this->messageBus->expects('dispatch')->with(Mockery::on(
            static function (DeleteAttachmentCommand $message) use ($attachmentIdB, $dossierId) {
                self::assertEquals($attachmentIdB, $message->attachmentId);
                self::assertEquals($dossierId, $message->dossierId);

                return true;
            }
        ))->andReturns(new Envelope(new stdClass()));

        $this->strategy->delete($dossier);
    }

    public function testDeleteAttachmentsWithOverride(): void
    {
        $attachmentA = Mockery::mock(CovenantAttachment::class);
        $attachmentA->shouldReceive('getId')->andReturn($attachmentIdA = Uuid::v6());

        $attachmentB = Mockery::mock(CovenantAttachment::class);
        $attachmentB->shouldReceive('getId')->andReturn($attachmentIdB = Uuid::v6());

        $dossier = Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getId')->andReturn($dossierId = Uuid::v6());
        $dossier->shouldReceive('getAttachments')->andReturn(new ArrayCollection([
            $attachmentA,
            $attachmentB,
        ]));

        $this->messageBus->expects('dispatch')->with(Mockery::on(
            static function (DeleteAttachmentWithOverrideCommand $message) use ($attachmentIdA, $dossierId) {
                self::assertEquals($attachmentIdA, $message->attachmentId);
                self::assertEquals($dossierId, $message->dossierId);

                return true;
            }
        ))->andReturns(new Envelope(new stdClass()));

        $this->messageBus->expects('dispatch')->with(Mockery::on(
            static function (DeleteAttachmentWithOverrideCommand $message) use ($attachmentIdB, $dossierId) {
                self::assertEquals($attachmentIdB, $message->attachmentId);
                self::assertEquals($dossierId, $message->dossierId);

                return true;
            }
        ))->andReturns(new Envelope(new stdClass()));

        $this->strategy->deleteWithOverride($dossier);
    }
}
