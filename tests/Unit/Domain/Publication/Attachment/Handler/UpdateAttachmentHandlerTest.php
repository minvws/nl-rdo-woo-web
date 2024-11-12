<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Attachment\Handler;

use App\Domain\Publication\Attachment\AttachmentRepository;
use App\Domain\Publication\Attachment\Command\UpdateAttachmentCommand;
use App\Domain\Publication\Attachment\Event\AttachmentUpdatedEvent;
use App\Domain\Publication\Attachment\Exception\AttachmentNotFoundException;
use App\Domain\Publication\Attachment\Handler\UpdateAttachmentHandler;
use App\Domain\Publication\Dossier\DossierRepository;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportAttachment;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Entity\FileInfo;
use App\Service\Uploader\UploaderService;
use App\Service\Uploader\UploadGroupId;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UpdateAttachmentHandlerTest extends MockeryTestCase
{
    private AttachmentRepository&MockInterface $attachmentRepository;
    private MessageBusInterface&MockInterface $messageBus;
    private DossierWorkflowManager&MockInterface $dossierWorkflowManager;
    private UpdateAttachmentHandler $handler;
    private DossierRepository&MockInterface $dossierRepository;
    private ValidatorInterface&MockInterface $validator;
    private UploaderService&MockInterface $uploaderService;

    public function setUp(): void
    {
        $this->attachmentRepository = \Mockery::mock(AttachmentRepository::class);
        $this->dossierRepository = \Mockery::mock(DossierRepository::class);
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);
        $this->dossierWorkflowManager = \Mockery::mock(DossierWorkflowManager::class);
        $this->validator = \Mockery::mock(ValidatorInterface::class);
        $this->uploaderService = \Mockery::mock(UploaderService::class);

        $this->handler = new UpdateAttachmentHandler(
            $this->messageBus,
            $this->dossierWorkflowManager,
            $this->attachmentRepository,
            $this->dossierRepository,
            $this->validator,
            $this->uploaderService,
        );

        parent::setUp();
    }

    public function testExceptionIsThrownWhenDocumentCannotBeFound(): void
    {
        $docUuid = Uuid::v6();
        $dossierUuid = Uuid::v6();
        $dossier = \Mockery::mock(AnnualReport::class);
        $dossier->shouldReceive('getId')->andReturn($dossierUuid);
        $dossier->shouldReceive('getAttachmentEntityClass')->andReturn(CovenantAttachment::class);

        $this->dossierRepository->shouldReceive('findOneByDossierId')->with($dossierUuid)->andReturn($dossier);
        $this->attachmentRepository->expects('findOneOrNullForDossier')->with($dossierUuid, $docUuid)->andReturnNull();

        $this->expectException(AttachmentNotFoundException::class);

        $this->handler->__invoke(
            new UpdateAttachmentCommand($dossierUuid, $docUuid)
        );
    }

    public function testInvoke(): void
    {
        $uploadRef = 'foo-123';

        $command = new UpdateAttachmentCommand(
            $dossierUuid = Uuid::v6(),
            $attachmentUuid = Uuid::v6(),
            uploadFileReference: $uploadRef,
        );

        $dossier = \Mockery::mock(AnnualReport::class);
        $dossier->shouldReceive('getId')->andReturn($dossierUuid);
        $dossier->shouldReceive('getAttachmentEntityClass')->andReturn(CovenantAttachment::class);

        $attachment = \Mockery::mock(AnnualReportAttachment::class);
        $attachment->shouldReceive('getId')->andReturn($attachmentUuid);
        $attachment->shouldReceive('getUploadGroupId')->andReturn(UploadGroupId::ATTACHMENTS);
        $attachment->shouldReceive('getDossier')->andReturn($dossier);
        $attachment->shouldReceive('getFileInfo')->andReturn(new FileInfo());

        $this->dossierRepository->shouldReceive('findOneByDossierId')->with($dossierUuid)->andReturn($dossier);
        $this->attachmentRepository->expects('findOneOrNullForDossier')->with($dossierUuid, $attachmentUuid)->andReturn($attachment);

        $this->dossierWorkflowManager->expects('applyTransition')->with($dossier, DossierStatusTransition::UPDATE_ATTACHMENT);

        $violations = \Mockery::mock(ConstraintViolationListInterface::class);
        $violations->expects('count')->andReturn(0);

        $this->validator->expects('validate')->with($attachment)->andReturn($violations);

        $this->attachmentRepository->expects('save')->with($attachment, true);

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (AttachmentUpdatedEvent $event) use ($attachmentUuid) {
                self::assertEquals($attachmentUuid, $event->attachmentId);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->uploaderService->expects('attachFileToEntity')->with($uploadRef, $attachment, UploadGroupId::ATTACHMENTS);

        $this->handler->__invoke($command);
    }
}
