<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\History;

use App\Domain\Publication\Attachment\Enum\AttachmentWithdrawReason;
use App\Domain\Publication\Attachment\Event\AttachmentCreatedEvent;
use App\Domain\Publication\Attachment\Event\AttachmentDeletedEvent;
use App\Domain\Publication\Attachment\Event\AttachmentUpdatedEvent;
use App\Domain\Publication\Attachment\Event\AttachmentWithdrawnEvent;
use App\Domain\Publication\Dossier\DossierRepository;
use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use App\Domain\Publication\FileInfo;
use App\Domain\Publication\History\AttachmentHistoryHandler;
use App\Service\HistoryService;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
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
        $this->historyService = \Mockery::mock(HistoryService::class);
        $this->translator = \Mockery::mock(TranslatorInterface::class);
        $this->repository = \Mockery::mock(DossierRepository::class);

        $this->handler = new AttachmentHistoryHandler(
            $this->historyService,
            $this->repository,
            $this->translator,
        );

        parent::setUp();
    }

    public function testHandleCreateOnPublishedDossier(): void
    {
        $fileInfo = $this->getFileInfo(
            $expectedName = 'my-file-name',
            $expectedType = 'my-file-type',
            $expectedSize = 123,
        );
        $dossier = $this->getDossier(DossierStatus::PUBLISHED);
        $attachment = $this->getAttachment($fileInfo, $dossier);

        $this->repository->shouldReceive('findOneByDossierId')->with($dossier->getId())->andReturn($dossier);

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
            )
            ->once();

        $this->handler->handleCreate($event);
    }

    public function testHandleCreateOnUnpublishedDossier(): void
    {
        $fileInfo = $this->getFileInfo(
            $expectedName = 'my-file-name',
            $expectedType = 'my-file-type',
            $expectedSize = 123,
        );
        $dossier = $this->getDossier(DossierStatus::CONCEPT);
        $attachment = $this->getAttachment($fileInfo, $dossier);

        $event = AttachmentCreatedEvent::forAttachment($attachment);

        $this->repository->shouldReceive('findOneByDossierId')->with($dossier->getId())->andReturn($dossier);

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
            )
            ->once();

        $this->handler->handleCreate($event);
    }

    public function testHandleUpdateOnPublishedDossier(): void
    {
        $fileInfo = $this->getFileInfo(
            $expectedName = 'my-file-name',
            $expectedType = 'my-file-type',
            $expectedSize = 123,
        );
        $dossier = $this->getDossier(DossierStatus::PUBLISHED);
        $attachment = $this->getAttachment($fileInfo, $dossier);

        $event = AttachmentUpdatedEvent::forAttachment($attachment);

        $this->repository->shouldReceive('findOneByDossierId')->with($dossier->getId())->andReturn($dossier);

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
            )
            ->once();

        $this->handler->handleUpdate($event);
    }

    public function testHandleUpdateOnUnpublishedDossier(): void
    {
        $fileInfo = $this->getFileInfo(
            $expectedName = 'my-file-name',
            $expectedType = 'my-file-type',
            $expectedSize = 123,
        );
        $dossier = $this->getDossier(DossierStatus::CONCEPT);
        $attachment = $this->getAttachment($fileInfo, $dossier);

        $event = AttachmentUpdatedEvent::forAttachment($attachment);

        $this->repository->shouldReceive('findOneByDossierId')->with($dossier->getId())->andReturn($dossier);

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
            )
            ->once();

        $this->handler->handleUpdate($event);
    }

    public function testHandleDelete(): void
    {
        $fileInfo = $this->getFileInfo(
            $expectedName = 'my-file-name',
            $expectedType = 'my-file-type',
            $expectedSize = 123,
        );
        $dossier = $this->getDossier(DossierStatus::PUBLISHED);
        $attachment = $this->getAttachment($fileInfo, $dossier);

        $event = AttachmentDeletedEvent::forAttachment($attachment);

        $this->repository->shouldReceive('findOneByDossierId')->with($dossier->getId())->andReturn($dossier);

        $this->historyService
            ->shouldReceive('addDossierEntry')
            ->with(
                $dossier->getId(),
                'attachment_deleted',
                [
                    'filename' => $expectedName,
                    'filetype' => $expectedType,
                    'filesize' => "$expectedSize bytes",
                ],
                HistoryService::MODE_PRIVATE,
            )
            ->once();

        $this->handler->handleDelete($event);
    }

    public function testHandleWithdraw(): void
    {
        $fileInfo = $this->getFileInfo('my-file-name', 'my-file-type', 123);
        $dossier = $this->getDossier(DossierStatus::CONCEPT);
        $attachment = $this->getAttachment($fileInfo, $dossier);
        $attachment->shouldReceive('getWithdrawReason')->andReturn($reason = AttachmentWithdrawReason::UNRELATED);
        $attachment->shouldReceive('getWithdrawExplanation')->andReturn($explanation = 'foo bar');
        $attachment->shouldReceive('isWithdrawn')->andReturnTrue();

        $event = AttachmentWithdrawnEvent::forAttachment($attachment);

        $this->repository->shouldReceive('findOneByDossierId')->with($dossier->getId())->andReturn($dossier);

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
            )
            ->once();

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
            )
            ->once();

        $this->handler->handleWithdraw($event);
    }

    private function getDossier(DossierStatus $status): Covenant
    {
        $dossier = \Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getId')->andReturn(Uuid::v6());
        $dossier->shouldReceive('getStatus')->andReturn($status);

        return $dossier;
    }

    private function getFileInfo(string $name, string $type, int $size): FileInfo
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('getName')->andReturn($name);
        $fileInfo->shouldReceive('getType')->andReturn($type);
        $fileInfo->shouldReceive('getSize')->andReturn($size);

        return $fileInfo;
    }

    private function getAttachment(FileInfo $fileInfo, Covenant $dossier): MockInterface&CovenantAttachment
    {
        $attachment = \Mockery::mock(CovenantAttachment::class);
        $attachment->shouldReceive('getFileInfo')->andReturn($fileInfo);
        $attachment->shouldReceive('getFileInfo')->andReturn($fileInfo);
        $attachment->shouldReceive('getId')->andReturn(Uuid::v6());
        $attachment->shouldReceive('getDossier')->andReturn($dossier);

        return $attachment;
    }
}
