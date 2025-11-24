<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\MainDocument\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocument;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocumentRepository;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowException;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use Shared\Domain\Publication\FileInfo;
use Shared\Domain\Publication\MainDocument\Command\DeleteMainDocumentCommand;
use Shared\Domain\Publication\MainDocument\Event\MainDocumentDeletedEvent;
use Shared\Domain\Publication\MainDocument\Handler\DeleteMainDocumentHandler;
use Shared\Domain\Publication\MainDocument\MainDocumentDeleteStrategyInterface;
use Shared\Domain\Publication\MainDocument\MainDocumentNotFoundException;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Workflow\Exception\TransitionException;

class DeleteMainDocumentHandlerTest extends UnitTestCase
{
    private EntityManagerInterface&MockInterface $entityManager;
    private AnnualReportMainDocumentRepository&MockInterface $annualReportDocumentRepository;
    private MessageBusInterface&MockInterface $messageBus;
    private DossierWorkflowManager&MockInterface $dossierWorkflowManager;
    private DeleteMainDocumentHandler $handler;
    private DossierRepository&MockInterface $dossierRepository;
    private MainDocumentDeleteStrategyInterface&MockInterface $deleteStrategy;

    protected function setUp(): void
    {
        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);
        $this->annualReportDocumentRepository = \Mockery::mock(AnnualReportMainDocumentRepository::class);
        $this->dossierRepository = \Mockery::mock(DossierRepository::class);
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);
        $this->dossierWorkflowManager = \Mockery::mock(DossierWorkflowManager::class);
        $this->deleteStrategy = \Mockery::mock(MainDocumentDeleteStrategyInterface::class);

        $this->handler = new DeleteMainDocumentHandler(
            $this->messageBus,
            $this->dossierWorkflowManager,
            $this->entityManager,
            $this->dossierRepository,
            [$this->deleteStrategy],
        );

        parent::setUp();
    }

    public function testEntityIsDeleted(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('getName')->andReturn('x');
        $fileInfo->shouldReceive('getType')->andReturn('y');
        $fileInfo->shouldReceive('getSize')->andReturn('z');

        $docUuid = Uuid::v6();
        $annualReportDocument = \Mockery::mock(AnnualReportMainDocument::class);
        $annualReportDocument->shouldReceive('getId')->andReturn($docUuid);
        $annualReportDocument->shouldReceive('getFileInfo')->andReturn($fileInfo);

        $dossierUuid = Uuid::v6();
        $dossier = \Mockery::mock(AnnualReport::class)->makePartial();
        $dossier->shouldReceive('getId')->andReturn($dossierUuid);
        $dossier->shouldReceive('getMainDocument')->andReturn($annualReportDocument);
        $dossier->expects('setMainDocument')->with(null);
        $dossier->shouldReceive('getMainDocumentEntityClass')->andReturn(AnnualReportMainDocument::class);

        $this->entityManager
            ->shouldReceive('getRepository')
            ->with(AnnualReportMainDocument::class)
            ->andReturn($this->annualReportDocumentRepository);

        $annualReportDocument->shouldReceive('getDossier')->andReturn($dossier);

        $this->dossierRepository->shouldReceive('findOneByDossierId')->with($dossierUuid)->andReturn($dossier);

        $this->dossierWorkflowManager->expects('applyTransition')->with($dossier, DossierStatusTransition::DELETE_MAIN_DOCUMENT);

        $this->annualReportDocumentRepository->expects('findOneByDossierId')->with($dossierUuid)->andReturn($annualReportDocument);
        $this->annualReportDocumentRepository->expects('remove')->with($annualReportDocument, true);

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static fn (MainDocumentDeletedEvent $message) => $message->documentId === $annualReportDocument->getId()
        ))->andReturns(new Envelope(new \stdClass()));

        $this->deleteStrategy->expects('delete')->with($annualReportDocument);

        $this->handler->__invoke(
            new DeleteMainDocumentCommand($dossierUuid)
        );
    }

    public function testEntityIsNotDeletedWhenTheWorkflowTransitionFails(): void
    {
        $docUuid = Uuid::v6();
        $annualReportDocument = \Mockery::mock(AnnualReportMainDocument::class);
        $annualReportDocument->shouldReceive('getId')->andReturn($docUuid);

        $dossierUuid = Uuid::v6();
        $dossier = \Mockery::mock(AnnualReport::class)->makePartial();
        $dossier->shouldReceive('getId')->andReturn($dossierUuid);
        $dossier->shouldReceive('getMainDocument')->andReturn($annualReportDocument);
        $dossier->shouldReceive('getMainDocumentEntityClass')->andReturn(AnnualReportMainDocument::class);

        $this->entityManager
            ->shouldReceive('getRepository')
            ->with(AnnualReportMainDocument::class)
            ->andReturn($this->annualReportDocumentRepository);

        $this->dossierRepository->shouldReceive('findOneByDossierId')->with($dossierUuid)->andReturn($dossier);

        $transition = DossierStatusTransition::DELETE_MAIN_DOCUMENT;
        $this->dossierWorkflowManager
            ->expects('applyTransition')
            ->with($dossier, $transition)
            ->andThrows(
                DossierWorkflowException::forTransitionFailed(
                    $dossier,
                    $transition,
                    \Mockery::mock(TransitionException::class),
                )
            );

        $this->annualReportDocumentRepository->expects('findOneByDossierId')->with($dossierUuid)->andReturn($annualReportDocument);

        $this->expectException(DossierWorkflowException::class);

        $this->handler->__invoke(
            new DeleteMainDocumentCommand($dossierUuid)
        );
    }

    public function testExceptionIsThrownWhenDocumentCannotBeFound(): void
    {
        $dossierUuid = Uuid::v6();
        $dossier = \Mockery::mock(AnnualReport::class)->makePartial();
        $dossier->shouldReceive('getId')->andReturn($dossierUuid);
        $dossier->shouldReceive('getMainDocumentEntityClass')->andReturn(AnnualReportMainDocument::class);

        $this->entityManager
            ->shouldReceive('getRepository')
            ->with(AnnualReportMainDocument::class)
            ->andReturn($this->annualReportDocumentRepository);

        $this->dossierRepository->shouldReceive('findOneByDossierId')->with($dossierUuid)->andReturn($dossier);

        $this->annualReportDocumentRepository->expects('findOneByDossierId')->with($dossierUuid)->andReturnNull();

        $this->expectException(MainDocumentNotFoundException::class);

        $this->handler->__invoke(
            new DeleteMainDocumentCommand($dossierUuid)
        );
    }
}
