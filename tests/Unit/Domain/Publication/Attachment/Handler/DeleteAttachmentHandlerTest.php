<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Attachment\Handler;

use App\Domain\Publication\Attachment\Command\DeleteAttachmentCommand;
use App\Domain\Publication\Attachment\Exception\AttachmentNotFoundException;
use App\Domain\Publication\Attachment\Handler\DeleteAttachmentHandler;
use App\Domain\Publication\Dossier\AbstractDossierRepository;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportAttachmentRepository;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Service\Storage\DocumentStorageService;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class DeleteAttachmentHandlerTest extends MockeryTestCase
{
    private AnnualReportAttachmentRepository&MockInterface $attachmentRepository;
    private MessageBusInterface&MockInterface $messageBus;
    private DossierWorkflowManager&MockInterface $dossierWorkflowManager;
    private EntityManagerInterface&MockInterface $entityManager;
    private DeleteAttachmentHandler $handler;
    private AbstractDossierRepository&MockInterface $dossierRepository;
    private DocumentStorageService&MockInterface $documentStorage;

    public function setUp(): void
    {
        $this->attachmentRepository = \Mockery::mock(AnnualReportAttachmentRepository::class);
        $this->dossierRepository = \Mockery::mock(AbstractDossierRepository::class);
        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);
        $this->dossierWorkflowManager = \Mockery::mock(DossierWorkflowManager::class);
        $this->documentStorage = \Mockery::mock(DocumentStorageService::class);

        $this->handler = new DeleteAttachmentHandler(
            $this->messageBus,
            $this->dossierWorkflowManager,
            $this->entityManager,
            $this->dossierRepository,
            $this->documentStorage,
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
            new DeleteAttachmentCommand($dossierUuid, $docUuid)
        );
    }
}
