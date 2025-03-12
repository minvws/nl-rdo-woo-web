<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Attachment;

use App\Domain\Publication\Attachment\AttachmentDispatcher;
use App\Domain\Publication\Attachment\Command\WithDrawAttachmentCommand;
use App\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use App\Domain\Publication\Attachment\Enum\AttachmentType;
use App\Domain\Publication\Attachment\Enum\AttachmentWithdrawReason;
use App\Domain\Publication\Attachment\Event\AttachmentCreatedEvent;
use App\Domain\Publication\Attachment\Event\AttachmentUpdatedEvent;
use App\Domain\Publication\Attachment\Event\AttachmentWithdrawnEvent;
use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use App\Domain\Publication\FileInfo;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class AttachmentDispatcherTest extends UnitTestCase
{
    private MessageBusInterface&MockInterface $messageBus;
    private AttachmentDispatcher $dispatcher;

    public function setUp(): void
    {
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);

        $this->dispatcher = new AttachmentDispatcher(
            $this->messageBus,
        );
    }

    public function testDispatchAttachmentUpdatedEvent(): void
    {
        $dossier = \Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getId')->andReturn($dossierId = Uuid::v6());

        $attachment = new CovenantAttachment(
            $dossier,
            new \DateTimeImmutable(),
            AttachmentType::ADVICE,
            AttachmentLanguage::DUTCH,
        );

        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('getName')->andReturn($fileName = 'foo');
        $fileInfo->shouldReceive('getType')->andReturn($fileType = 'pdf');
        $fileInfo->shouldReceive('getSize')->andReturn(123);

        $attachment->setFileInfo($fileInfo);

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (AttachmentUpdatedEvent $event) use ($attachment, $dossierId, $fileName, $fileType) {
                self::assertEquals($dossierId, $event->dossierId);
                self::assertEquals($attachment->getId(), $event->attachmentId);
                self::assertEquals($fileName, $event->fileName);
                self::assertEquals($fileType, $event->fileType);
                self::assertEquals('123 bytes', $event->fileSize);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->dispatcher->dispatchAttachmentUpdatedEvent($attachment);
    }

    public function testDispatchAttachmentCreatedEvent(): void
    {
        $dossier = \Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getId')->andReturn($dossierId = Uuid::v6());

        $attachment = new CovenantAttachment(
            $dossier,
            new \DateTimeImmutable(),
            AttachmentType::ADVICE,
            AttachmentLanguage::DUTCH,
        );

        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('getName')->andReturn($fileName = 'foo');
        $fileInfo->shouldReceive('getType')->andReturn($fileType = 'pdf');
        $fileInfo->shouldReceive('getSize')->andReturn(123);

        $attachment->setFileInfo($fileInfo);

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (AttachmentCreatedEvent $event) use ($attachment, $dossierId, $fileName, $fileType) {
                self::assertEquals($dossierId, $event->dossierId);
                self::assertEquals($attachment->getId(), $event->attachmentId);
                self::assertEquals($fileName, $event->fileName);
                self::assertEquals($fileType, $event->fileType);
                self::assertEquals('123 bytes', $event->fileSize);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->dispatcher->dispatchAttachmentCreatedEvent($attachment);
    }

    public function testDispatchAttachmentWithdrawnEvent(): void
    {
        $dossier = \Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getId')->andReturn($dossierId = Uuid::v6());
        $dossier->shouldReceive('getStatus')->andReturn(DossierStatus::PUBLISHED);

        $attachment = new CovenantAttachment(
            $dossier,
            new \DateTimeImmutable(),
            AttachmentType::ADVICE,
            AttachmentLanguage::DUTCH,
        );

        $fileInfo = new FileInfo();
        $fileInfo->setUploaded(true);

        $attachment->setFileInfo($fileInfo);

        $attachment->withdraw(AttachmentWithdrawReason::UNRELATED, 'foo bar');

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (AttachmentWithdrawnEvent $event) use ($attachment, $dossierId) {
                self::assertEquals($dossierId, $event->dossierId);
                self::assertEquals($attachment->getId(), $event->attachmentId);
                self::assertEquals($attachment->getWithdrawReason(), $event->reason);
                self::assertEquals($attachment->getWithdrawExplanation(), $event->explanation);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->dispatcher->dispatchAttachmentWithdrawnEvent($attachment);
    }

    public function testDispatchWithdrawAttachmentCommand(): void
    {
        $dossier = \Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getId')->andReturn($dossierId = Uuid::v6());

        $attachment = new CovenantAttachment(
            $dossier,
            new \DateTimeImmutable(),
            AttachmentType::ADVICE,
            AttachmentLanguage::DUTCH,
        );

        $reason = AttachmentWithdrawReason::INCOMPLETE;
        $explanation = 'foo bar';

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (WithDrawAttachmentCommand $event) use ($attachment, $dossierId, $reason, $explanation) {
                self::assertEquals($dossierId, $event->dossierId);
                self::assertEquals($attachment->getId(), $event->attachmentId);
                self::assertEquals($reason, $event->reason);
                self::assertEquals($explanation, $event->explanation);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->dispatcher->dispatchWithdrawAttachmentCommand(
            $dossier,
            $attachment,
            $reason,
            $explanation,
        );
    }
}
