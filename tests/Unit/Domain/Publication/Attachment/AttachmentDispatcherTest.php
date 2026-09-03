<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Attachment;

use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
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
use Shared\ValueObject\PlainDate;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class AttachmentDispatcherTest extends UnitTestCase
{
    private MessageBusInterface&MockInterface $messageBus;
    private AttachmentDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->messageBus = Mockery::mock(MessageBusInterface::class);

        $this->dispatcher = new AttachmentDispatcher(
            $this->messageBus,
        );
    }

    /**
     * @return array<string, array{method: string, fileUpdated: bool, metadataUpdated: bool}>
     */
    public static function updatedEventDataProvider(): array
    {
        return [
            'metadata only' => [
                'method' => 'dispatchAttachmentMetadataUpdatedEvent',
                'fileUpdated' => false,
                'metadataUpdated' => true,
            ],
            'file only' => [
                'method' => 'dispatchAttachmentFileUpdatedEvent',
                'fileUpdated' => true,
                'metadataUpdated' => false,
            ],
            'metadata and file' => [
                'method' => 'dispatchAttachmentMetadataAndFileUpdatedEvent',
                'fileUpdated' => true,
                'metadataUpdated' => true,
            ],
        ];
    }

    #[DataProvider('updatedEventDataProvider')]
    public function testDispatchAttachmentUpdatedEvent(string $method, bool $fileUpdated, bool $metadataUpdated): void
    {
        $dossier = Mockery::mock(Covenant::class);
        $dossier->expects('getId')->andReturn($dossierId = Uuid::v6());

        $attachment = new CovenantAttachment(
            $dossier,
            PlainDate::today(),
            AttachmentType::ADVICE,
            AttachmentLanguage::NLD,
        );

        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('getName')->andReturn($fileName = 'foo');
        $fileInfo->expects('getType')->andReturn($fileType = 'pdf');
        $fileInfo->expects('getSize')->andReturn(123);

        $attachment->setFileInfo($fileInfo);

        $this->messageBus->expects('dispatch')->with(Mockery::on(
            static function (AttachmentUpdatedEvent $event) use (
                $attachment,
                $dossierId,
                $fileName,
                $fileType,
                $fileUpdated,
                $metadataUpdated,
            ) {
                self::assertEquals($dossierId, $event->dossierId);
                self::assertEquals($attachment->getId(), $event->attachmentId);
                self::assertEquals($fileName, $event->fileName);
                self::assertEquals($fileType, $event->fileType);
                self::assertEquals('123 bytes', $event->fileSize);
                self::assertSame($fileUpdated, $event->fileUpdated);
                self::assertSame($metadataUpdated, $event->metadataUpdated);

                return true;
            },
        ))->andReturns(new Envelope(new stdClass()));

        $this->dispatcher->{$method}($attachment);
    }

    public function testDispatchAttachmentCreatedEvent(): void
    {
        $dossier = Mockery::mock(Covenant::class);
        $dossier->expects('getId')->andReturn($dossierId = Uuid::v6());

        $attachment = new CovenantAttachment(
            $dossier,
            PlainDate::today(),
            AttachmentType::ADVICE,
            AttachmentLanguage::NLD,
        );

        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('getName')->andReturn($fileName = 'foo');
        $fileInfo->expects('getType')->andReturn($fileType = 'pdf');
        $fileInfo->expects('getSize')->andReturn(123);

        $attachment->setFileInfo($fileInfo);

        $this->messageBus->expects('dispatch')->with(Mockery::on(
            static function (AttachmentCreatedEvent $event) use ($attachment, $dossierId, $fileName, $fileType) {
                self::assertEquals($dossierId, $event->dossierId);
                self::assertEquals($attachment->getId(), $event->attachmentId);
                self::assertEquals($fileName, $event->fileName);
                self::assertEquals($fileType, $event->fileType);
                self::assertEquals('123 bytes', $event->fileSize);

                return true;
            },
        ))->andReturns(new Envelope(new stdClass()));

        $this->dispatcher->dispatchAttachmentCreatedEvent($attachment);
    }

    public function testDispatchAttachmentWithdrawnEvent(): void
    {
        $dossier = Mockery::mock(Covenant::class);
        $dossier->expects('getId')->andReturn($dossierId = Uuid::v6());
        $dossier->expects('getStatus')->andReturn(DossierStatus::PUBLISHED);

        $attachment = new CovenantAttachment(
            $dossier,
            PlainDate::today(),
            AttachmentType::ADVICE,
            AttachmentLanguage::NLD,
        );

        $fileInfo = new FileInfo();
        $fileInfo->setUploaded(true);

        $attachment->setFileInfo($fileInfo);

        $attachment->withdraw(AttachmentWithdrawReason::UNRELATED, 'foo bar');

        $this->messageBus->expects('dispatch')->with(Mockery::on(
            static function (AttachmentWithdrawnEvent $event) use ($attachment, $dossierId) {
                self::assertEquals($dossierId, $event->dossierId);
                self::assertEquals($attachment->getId(), $event->attachmentId);
                self::assertEquals($attachment->getWithdrawReason(), $event->reason);
                self::assertEquals($attachment->getWithdrawExplanation(), $event->explanation);

                return true;
            },
        ))->andReturns(new Envelope(new stdClass()));

        $this->dispatcher->dispatchAttachmentWithdrawnEvent($attachment);
    }

    public function testDispatchWithdrawAttachmentCommand(): void
    {
        $dossier = Mockery::mock(Covenant::class);
        $dossier->expects('getId')->andReturn($dossierId = Uuid::v6());

        $attachment = new CovenantAttachment(
            $dossier,
            PlainDate::today(),
            AttachmentType::ADVICE,
            AttachmentLanguage::NLD,
        );

        $reason = AttachmentWithdrawReason::INCOMPLETE;
        $explanation = 'foo bar';

        $this->messageBus->expects('dispatch')->with(Mockery::on(
            static function (WithDrawAttachmentCommand $event) use ($attachment, $dossierId, $reason, $explanation) {
                self::assertEquals($dossierId, $event->dossierId);
                self::assertEquals($attachment->getId(), $event->attachmentId);
                self::assertEquals($reason, $event->reason);
                self::assertEquals($explanation, $event->explanation);

                return true;
            },
        ))->andReturns(new Envelope(new stdClass()));

        $this->dispatcher->dispatchWithdrawAttachmentCommand(
            $dossier,
            $attachment,
            $reason,
            $explanation,
        );
    }
}
