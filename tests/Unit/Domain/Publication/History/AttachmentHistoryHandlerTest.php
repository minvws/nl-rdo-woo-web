<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\History;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Attachment\Enum\AttachmentWithdrawReason;
use Shared\Domain\Publication\Attachment\Event\AttachmentCreatedEvent;
use Shared\Domain\Publication\Attachment\Event\AttachmentDeletedEvent;
use Shared\Domain\Publication\Attachment\Event\AttachmentUpdatedEvent;
use Shared\Domain\Publication\Attachment\Event\AttachmentWithdrawnEvent;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use Shared\Domain\Publication\FileInfo;
use Shared\Domain\Publication\History\AttachmentHistoryHandler;
use Shared\Service\HistoryService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AttachmentHistoryHandlerTest extends UnitTestCase
{
    private HistoryService&MockInterface $historyService;
    private TranslatorInterface&MockInterface $translator;
    private DossierRepository&MockInterface $repository;
    private AttachmentHistoryHandler $handler;

    protected function setUp(): void
    {
        $this->historyService = Mockery::mock(HistoryService::class);
        $this->translator = Mockery::mock(TranslatorInterface::class);
        $this->repository = Mockery::mock(DossierRepository::class);

        $this->handler = new AttachmentHistoryHandler(
            $this->historyService,
            $this->repository,
            $this->translator,
        );

        parent::setUp();
    }

    public function testHandleCreateOnPublishedDossier(): void
    {
        $expectedName = 'my-file-name';
        $expectedType = 'my-file-type';
        $expectedSize = 123;

        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('getName')->andReturn($expectedName);
        $fileInfo->expects('getType')->andReturn($expectedType);
        $fileInfo->expects('getSize')->andReturn($expectedSize);

        $dossier = Mockery::mock(Covenant::class);
        $dossier->expects('getId')->times(4)->andReturn(Uuid::v6());
        $dossier->expects('getStatus')->andReturn(DossierStatus::PUBLISHED);

        $attachment = Mockery::mock(CovenantAttachment::class);
        $attachment->expects('getFileInfo')->times(2)->andReturn($fileInfo);
        $attachment->expects('getFileInfo')->andReturn($fileInfo);
        $attachment->expects('getId')->andReturn(Uuid::v6());
        $attachment->expects('getDossier')->andReturn($dossier);

        $this->repository->expects('findOneByDossierId')->with($dossier->getId())->andReturn($dossier);

        $event = AttachmentCreatedEvent::forAttachment($attachment);

        $this->historyService
            ->expects('addDossierEntry')
            ->with(
                $dossier->getId(),
                'attachment_created',
                [
                    'filename' => $expectedName,
                    'filetype' => $expectedType,
                    'filesize' => "$expectedSize bytes",
                ],
                HistoryService::MODE_BOTH,
            );

        $this->handler->handleCreate($event);
    }

    public function testHandleCreateOnUnpublishedDossier(): void
    {
        $expectedName = 'my-file-name';
        $expectedType = 'my-file-type';
        $expectedSize = 123;

        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('getName')->andReturn($expectedName);
        $fileInfo->expects('getType')->andReturn($expectedType);
        $fileInfo->expects('getSize')->andReturn($expectedSize);

        $dossier = Mockery::mock(Covenant::class);
        $dossier->expects('getId')->times(4)->andReturn(Uuid::v6());
        $dossier->expects('getStatus')->andReturn(DossierStatus::CONCEPT);

        $attachment = Mockery::mock(CovenantAttachment::class);
        $attachment->expects('getFileInfo')->times(3)->andReturn($fileInfo);
        $attachment->expects('getId')->andReturn(Uuid::v6());
        $attachment->expects('getDossier')->andReturn($dossier);

        $event = AttachmentCreatedEvent::forAttachment($attachment);

        $this->repository->expects('findOneByDossierId')->with($dossier->getId())->andReturn($dossier);

        $this->historyService
            ->expects('addDossierEntry')
            ->with(
                $dossier->getId(),
                'attachment_created',
                [
                    'filename' => $expectedName,
                    'filetype' => $expectedType,
                    'filesize' => "$expectedSize bytes",
                ],
                HistoryService::MODE_PRIVATE,
            );

        $this->handler->handleCreate($event);
    }

    public function testHandleUpdateOnPublishedDossier(): void
    {
        $expectedName = 'my-file-name';
        $expectedType = 'my-file-type';
        $expectedSize = 123;

        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('getName')->andReturn($expectedName);
        $fileInfo->expects('getType')->andReturn($expectedType);
        $fileInfo->expects('getSize')->andReturn($expectedSize);

        $dossier = Mockery::mock(Covenant::class);
        $dossier->expects('getId')->times(3)->andReturn(Uuid::v6());
        $dossier->expects('getStatus')->andReturn(DossierStatus::PUBLISHED);

        $attachment = Mockery::mock(CovenantAttachment::class);
        $attachment->expects('getFileInfo')->times(3)->andReturn($fileInfo);
        $attachment->expects('getId')->andReturn(Uuid::v6());
        $attachment->expects('getDossier')->andReturn($dossier);

        $event = AttachmentUpdatedEvent::forAttachment($attachment);

        $this->repository->expects('findOneByDossierId')->with($dossier->getId())->andReturn($dossier);

        $this->historyService
            ->expects('addDossierEntry')
            ->with(
                $dossier->getId(),
                'attachment_updated',
                [
                    'filename' => $expectedName,
                    'filetype' => $expectedType,
                    'filesize' => "$expectedSize bytes",
                ],
                HistoryService::MODE_BOTH,
            );

        $this->handler->handleUpdate($event);
    }

    public function testHandleUpdateOnUnpublishedDossier(): void
    {
        $expectedName = 'my-file-name';
        $expectedType = 'my-file-type';
        $expectedSize = 123;

        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('getName')->andReturn($expectedName);
        $fileInfo->expects('getType')->andReturn($expectedType);
        $fileInfo->expects('getSize')->andReturn($expectedSize);

        $dossier = Mockery::mock(Covenant::class);
        $dossier->expects('getId')->times(3)->andReturn(Uuid::v6());
        $dossier->expects('getStatus')->andReturn(DossierStatus::CONCEPT);

        $attachment = Mockery::mock(CovenantAttachment::class);
        $attachment->expects('getFileInfo')->times(3)->andReturn($fileInfo);
        $attachment->expects('getId')->andReturn(Uuid::v6());
        $attachment->expects('getDossier')->andReturn($dossier);

        $event = AttachmentUpdatedEvent::forAttachment($attachment);

        $this->repository->expects('findOneByDossierId')->with($dossier->getId())->andReturn($dossier);

        $this->historyService
            ->expects('addDossierEntry')
            ->with(
                $dossier->getId(),
                'attachment_updated',
                [
                    'filename' => $expectedName,
                    'filetype' => $expectedType,
                    'filesize' => "$expectedSize bytes",
                ],
                HistoryService::MODE_PRIVATE,
            );

        $this->handler->handleUpdate($event);
    }

    public function testHandleDelete(): void
    {
        $expectedName = 'my-file-name';
        $expectedType = 'my-file-type';
        $expectedSize = 123;

        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('getName')->andReturn($expectedName);
        $fileInfo->expects('getType')->andReturn($expectedType);
        $fileInfo->expects('getSize')->andReturn($expectedSize);

        $dossier = Mockery::mock(Covenant::class);
        $dossier->expects('getId')->times(2)->andReturn(Uuid::v6());

        $attachment = Mockery::mock(CovenantAttachment::class);
        $attachment->expects('getFileInfo')->times(3)->andReturn($fileInfo);
        $attachment->expects('getId')->andReturn(Uuid::v6());
        $attachment->expects('getDossier')->andReturn($dossier);

        $event = AttachmentDeletedEvent::forAttachment($attachment);

        $this->historyService
            ->expects('addDossierEntry')
            ->with(
                $dossier->getId(),
                'attachment_deleted',
                [
                    'filename' => $expectedName,
                    'filetype' => $expectedType,
                    'filesize' => "$expectedSize bytes",
                ],
                HistoryService::MODE_PRIVATE,
            );

        $this->handler->handleDelete($event);
    }

    public function testHandleWithdraw(): void
    {
        $dossier = Mockery::mock(Covenant::class);
        $dossier->expects('getId')->times(3)->andReturn(Uuid::v6());

        $attachment = Mockery::mock(CovenantAttachment::class);
        $attachment->expects('getId')->andReturn(Uuid::v6());
        $attachment->expects('getDossier')->andReturn($dossier);
        $attachment->expects('getWithdrawReason')->times(2)->andReturn(AttachmentWithdrawReason::UNRELATED);
        $attachment->expects('getWithdrawExplanation')->times(2)->andReturn($explanation = 'foo bar');
        $attachment->expects('isWithdrawn')->andReturnTrue();

        $event = AttachmentWithdrawnEvent::forAttachment($attachment);

        $this->translator
            ->expects('trans')
            ->with('global.attachment.withdraw.reason.unrelated', [], null, null)
            ->andReturn($translatedReason = 'translated');

        $this->historyService
            ->expects('addDossierEntry')
            ->with(
                $dossier->getId(),
                'attachment_withdrawn',
                [
                    'reason' => $translatedReason,
                ],
                HistoryService::MODE_PUBLIC,
            );

        $this->historyService
            ->expects('addDossierEntry')
            ->with(
                $dossier->getId(),
                'attachment_withdrawn',
                [
                    'reason' => $translatedReason,
                    'explanation' => $explanation,
                ],
                HistoryService::MODE_PRIVATE,
            );

        $this->handler->handleWithdraw($event);
    }
}
