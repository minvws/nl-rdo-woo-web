<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Attachment\Handler;

use App\Domain\Publication\Attachment\AttachmentRepositoryInterface;
use App\Domain\Publication\Attachment\Command\UpdateAttachmentCommand;
use App\Domain\Publication\Attachment\Exception\AttachmentNotFoundException;
use App\Domain\Publication\Attachment\Handler\UpdateAttachmentHandler;
use App\Domain\Publication\Dossier\AbstractDossierRepository;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Service\Uploader\UploaderService;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UpdateAttachmentHandlerTest extends MockeryTestCase
{
    private EntityManagerInterface&MockInterface $entityManager;
    private MessageBusInterface&MockInterface $messageBus;
    private DossierWorkflowManager&MockInterface $dossierWorkflowManager;
    private UpdateAttachmentHandler $handler;
    private AbstractDossierRepository&MockInterface $dossierRepository;
    private AttachmentRepositoryInterface&MockInterface $attachmentRepository;
    private ValidatorInterface&MockInterface $validator;
    private UploaderService&MockInterface $uploaderService;

    public function setUp(): void
    {
        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);
        $this->dossierRepository = \Mockery::mock(AbstractDossierRepository::class);
        $this->attachmentRepository = \Mockery::mock(AttachmentRepositoryInterface::class);
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);
        $this->dossierWorkflowManager = \Mockery::mock(DossierWorkflowManager::class);
        $this->validator = \Mockery::mock(ValidatorInterface::class);
        $this->uploaderService = \Mockery::mock(UploaderService::class);

        $this->handler = new UpdateAttachmentHandler(
            $this->messageBus,
            $this->dossierWorkflowManager,
            $this->entityManager,
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

        $this->entityManager->shouldReceive('getRepository')->with(CovenantAttachment::class)->andReturn($this->attachmentRepository);
        $this->dossierRepository->shouldReceive('findOneByDossierId')->with($dossierUuid)->andReturn($dossier);
        $this->attachmentRepository->expects('findOneOrNullForDossier')->with($dossierUuid, $docUuid)->andReturnNull();

        $this->expectException(AttachmentNotFoundException::class);

        $this->handler->__invoke(
            new UpdateAttachmentCommand($dossierUuid, $docUuid)
        );
    }
}
