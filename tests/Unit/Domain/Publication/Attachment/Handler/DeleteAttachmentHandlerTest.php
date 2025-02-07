<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Attachment\Handler;

use App\Domain\Publication\Attachment\AttachmentDeleteStrategyInterface;
use App\Domain\Publication\Attachment\AttachmentRepository;
use App\Domain\Publication\Attachment\Command\DeleteAttachmentCommand;
use App\Domain\Publication\Attachment\Exception\AttachmentNotFoundException;
use App\Domain\Publication\Attachment\Handler\DeleteAttachmentHandler;
use App\Domain\Publication\Dossier\DossierRepository;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class DeleteAttachmentHandlerTest extends MockeryTestCase
{
    private AttachmentRepository&MockInterface $attachmentRepository;
    private MessageBusInterface&MockInterface $messageBus;
    private DossierWorkflowManager&MockInterface $dossierWorkflowManager;
    private DeleteAttachmentHandler $handler;
    private DossierRepository&MockInterface $dossierRepository;
    private AttachmentDeleteStrategyInterface&MockInterface $deleteStrategy;

    public function setUp(): void
    {
        $this->attachmentRepository = \Mockery::mock(AttachmentRepository::class);
        $this->dossierRepository = \Mockery::mock(DossierRepository::class);
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);
        $this->dossierWorkflowManager = \Mockery::mock(DossierWorkflowManager::class);
        $this->deleteStrategy = \Mockery::mock(AttachmentDeleteStrategyInterface::class);

        $this->handler = new DeleteAttachmentHandler(
            $this->messageBus,
            $this->dossierWorkflowManager,
            $this->attachmentRepository,
            $this->dossierRepository,
            [$this->deleteStrategy],
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
            new DeleteAttachmentCommand($dossierUuid, $docUuid)
        );
    }
}
