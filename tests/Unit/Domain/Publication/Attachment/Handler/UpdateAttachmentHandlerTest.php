<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Attachment\Handler;

use App\Domain\Publication\Attachment\AttachmentDispatcher;
use App\Domain\Publication\Attachment\Command\UpdateAttachmentCommand;
use App\Domain\Publication\Attachment\Exception\AttachmentNotFoundException;
use App\Domain\Publication\Attachment\Handler\AttachmentEntityLoader;
use App\Domain\Publication\Attachment\Handler\UpdateAttachmentHandler;
use App\Domain\Publication\Attachment\Repository\AttachmentRepository;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportAttachment;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\FileInfo;
use App\Service\Uploader\UploaderService;
use App\Service\Uploader\UploadGroupId;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UpdateAttachmentHandlerTest extends MockeryTestCase
{
    private AttachmentRepository&MockInterface $attachmentRepository;
    private UpdateAttachmentHandler $handler;
    private AttachmentEntityLoader&MockInterface $entityLoader;
    private AttachmentDispatcher&MockInterface $dispatcher;
    private ValidatorInterface&MockInterface $validator;
    private UploaderService&MockInterface $uploaderService;

    public function setUp(): void
    {
        $this->attachmentRepository = \Mockery::mock(AttachmentRepository::class);
        $this->entityLoader = \Mockery::mock(AttachmentEntityLoader::class);
        $this->dispatcher = \Mockery::mock(AttachmentDispatcher::class);
        $this->validator = \Mockery::mock(ValidatorInterface::class);
        $this->uploaderService = \Mockery::mock(UploaderService::class);

        $this->handler = new UpdateAttachmentHandler(
            $this->attachmentRepository,
            $this->validator,
            $this->uploaderService,
            $this->entityLoader,
            $this->dispatcher,
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

        $this->entityLoader
            ->expects('loadAndValidateAttachment')
            ->with($dossierUuid, $docUuid, DossierStatusTransition::UPDATE_ATTACHMENT)
            ->andThrows(new AttachmentNotFoundException());

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

        $this->entityLoader
            ->expects('loadAndValidateAttachment')
            ->with($dossierUuid, $attachmentUuid, DossierStatusTransition::UPDATE_ATTACHMENT)
            ->andReturn($attachment);

        $violations = \Mockery::mock(ConstraintViolationListInterface::class);
        $violations->expects('count')->andReturn(0);

        $this->validator->expects('validate')->with($attachment)->andReturn($violations);

        $this->attachmentRepository->expects('save')->with($attachment, true);

        $this->dispatcher->expects('dispatchAttachmentUpdatedEvent')->with($attachment);

        $this->uploaderService->expects('attachFileToEntity')->with($uploadRef, $attachment, UploadGroupId::ATTACHMENTS);

        $this->handler->__invoke($command);
    }
}
