<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Attachment;

use Mockery\MockInterface;
use Shared\Domain\Publication\Attachment\AttachmentDispatcher;
use Shared\Domain\Publication\Attachment\Command\WithDrawAttachmentCommand;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Attachment\Enum\AttachmentWithdrawReason;
use Shared\Domain\Publication\Attachment\Event\AttachmentCreatedEvent;
use Shared\Domain\Publication\Attachment\Event\AttachmentUpdatedEvent;
use Shared\Domain\Publication\Attachment\Event\AttachmentWithdrawnEvent;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use Shared\Domain\Publication\FileInfo;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class AttachmentDispatcherTest extends UnitTestCase
{
    private MessageBusInterface&MockInterface $messageBus;
    private AttachmentDispatcher $dispatcher;

    protected function setUp(): void
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
