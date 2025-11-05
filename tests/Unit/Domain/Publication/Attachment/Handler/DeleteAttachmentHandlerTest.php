<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Attachment\Handler;

use App\Domain\Publication\Attachment\AttachmentDeleter;
use App\Domain\Publication\Attachment\Command\DeleteAttachmentCommand;
use App\Domain\Publication\Attachment\Command\DeleteAttachmentWithOverrideCommand;
use App\Domain\Publication\Attachment\Handler\AttachmentEntityLoader;
use App\Domain\Publication\Attachment\Handler\DeleteAttachmentHandler;
use App\Domain\Publication\Attachment\Repository\AttachmentRepository;
use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class DeleteAttachmentHandlerTest extends MockeryTestCase
{
    private AttachmentRepository&MockInterface $attachmentRepository;
    private MessageBusInterface&MockInterface $messageBus;
    private AttachmentEntityLoader&MockInterface $entityLoader;
    private DeleteAttachmentHandler $handler;
    private AttachmentDeleter&MockInterface $deleter;

    protected function setUp(): void
    {
        $this->attachmentRepository = \Mockery::mock(AttachmentRepository::class);
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);
        $this->entityLoader = \Mockery::mock(AttachmentEntityLoader::class);
        $this->deleter = \Mockery::mock(AttachmentDeleter::class);

        $this->handler = new DeleteAttachmentHandler(
            $this->messageBus,
            $this->attachmentRepository,
            $this->entityLoader,
            $this->deleter,
        );

        parent::setUp();
    }

    public function testDeleteSuccessful(): void
    {
        $dossierUuid = Uuid::v6();
        $dossier = \Mockery::mock(AnnualReport::class);
        $dossier->shouldReceive('getId')->andReturn($dossierUuid);
        $dossier->shouldReceive('getAttachmentEntityClass')->andReturn(CovenantAttachment::class);
        $dossier->shouldReceive('getStatus')->andReturn(DossierStatus::DELETED);

        $attachmentId = Uuid::v6();
        $attachment = \Mockery::mock(CovenantAttachment::class);
        $attachment->shouldReceive('getId')->andReturn($attachmentId);
        $attachment->shouldReceive('getDossier')->andReturn($dossier);
        $attachment->shouldReceive('getFileInfo->getName')->andReturn('foo');
        $attachment->shouldReceive('getFileInfo->getType')->andReturn('pdf');
        $attachment->shouldReceive('getFileInfo->getSize')->andReturn(123);

        $this->entityLoader
            ->expects('loadAndValidateAttachment')
            ->with($dossierUuid, $attachmentId, DossierStatusTransition::DELETE_ATTACHMENT)
            ->andReturn($attachment);

        $this->deleter->expects('delete')->with($attachment);

        $this->attachmentRepository->expects('remove')->with($attachment, true);

        $this->handler->__invoke(
            new DeleteAttachmentCommand($dossierUuid, $attachmentId),
        );
    }

    public function testDeleteSuccessfulWithOverrideWorkflow(): void
    {
        $dossierUuid = Uuid::v6();
        $dossier = \Mockery::mock(AnnualReport::class);
        $dossier->shouldReceive('getId')->andReturn($dossierUuid);
        $dossier->shouldReceive('getAttachmentEntityClass')->andReturn(CovenantAttachment::class);
        $dossier->shouldReceive('getStatus')->andReturn(DossierStatus::DELETED);

        $attachmentId = Uuid::v6();
        $attachment = \Mockery::mock(CovenantAttachment::class);
        $attachment->shouldReceive('getId')->andReturn($attachmentId);
        $attachment->shouldReceive('getDossier')->andReturn($dossier);
        $attachment->shouldReceive('getFileInfo->getName')->andReturn('foo');
        $attachment->shouldReceive('getFileInfo->getType')->andReturn('pdf');
        $attachment->shouldReceive('getFileInfo->getSize')->andReturn(123);

        $this->entityLoader
            ->expects('loadAttachment')
            ->with($dossierUuid, $attachmentId)
            ->andReturn($attachment);

        $this->deleter->expects('delete')->with($attachment);

        $this->attachmentRepository->expects('remove')->with($attachment, true);

        $this->handler->__invoke(
            new DeleteAttachmentWithOverrideCommand($dossierUuid, $attachmentId),
        );
    }
}
