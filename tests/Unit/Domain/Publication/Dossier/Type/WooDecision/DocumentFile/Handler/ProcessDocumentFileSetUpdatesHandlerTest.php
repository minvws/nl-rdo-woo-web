<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Handler;

use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Command\ProcessDocumentFileSetUpdatesCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\DocumentFileDispatcher;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileSet;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileUpdate;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum\DocumentFileSetStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum\DocumentFileUpdateStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Handler\ProcessDocumentFileSetUpdatesHandler;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Repository\DocumentFileSetRepository;
use App\Tests\Unit\UnitTestCase;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

class ProcessDocumentFileSetUpdatesHandlerTest extends UnitTestCase
{
    private DocumentFileSetRepository&MockInterface $repository;
    private DocumentFileDispatcher&MockInterface $dispatcher;
    private LoggerInterface&MockInterface $logger;
    private ProcessDocumentFileSetUpdatesHandler $handler;

    public function setUp(): void
    {
        $this->dispatcher = \Mockery::mock(DocumentFileDispatcher::class);
        $this->repository = \Mockery::mock(DocumentFileSetRepository::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);

        $this->handler = new ProcessDocumentFileSetUpdatesHandler(
            $this->repository,
            $this->logger,
            $this->dispatcher,
        );

        parent::setUp();
    }

    public function testInvokeSuccessfully(): void
    {
        $id = Uuid::v6();
        $documentFileSet = \Mockery::mock(DocumentFileSet::class);
        $documentFileSet
            ->expects('getStatus')
            ->andReturn(DocumentFileSetStatus::CONFIRMED);

        $updateA = \Mockery::mock(DocumentFileUpdate::class);
        $updateA
            ->expects('getStatus')
            ->andReturn(DocumentFileUpdateStatus::COMPLETED);

        $updateB = \Mockery::mock(DocumentFileUpdate::class);
        $updateB
            ->expects('getStatus')
            ->andReturn(DocumentFileUpdateStatus::PENDING);

        $documentFileSet
            ->expects('getUpdates')
            ->andReturn(new ArrayCollection([
                $updateA,
                $updateB,
            ]));

        $this->repository
            ->expects('find')
            ->with($id)
            ->andReturn($documentFileSet);
        $this->repository
            ->expects('updateStatusTransactionally')
            ->with($documentFileSet, DocumentFileSetStatus::PROCESSING_UPDATES);

        $this->dispatcher->expects('dispatchProcessDocumentFileUpdateCommand')->with($updateB);

        $this->handler->__invoke(
            new ProcessDocumentFileSetUpdatesCommand($id),
        );
    }

    public function testInvokeLogsWarningAndAbortsWhenSetCannotBeLoaded(): void
    {
        $id = Uuid::v6();

        $this->repository->expects('find')->with($id)->andReturnNull();

        $this->logger->expects('warning');

        $this->handler->__invoke(
            new ProcessDocumentFileSetUpdatesCommand($id),
        );
    }

    public function testInvokeLogsWarningAndAbortsWhenSetIsRejected(): void
    {
        $id = Uuid::v6();
        $documentFileSet = \Mockery::mock(DocumentFileSet::class);
        $documentFileSet
            ->expects('getStatus')->twice()
            ->andReturn(DocumentFileSetStatus::REJECTED);
        $documentFileSet
            ->expects('getId')
            ->andReturn($id);

        $this->repository->expects('find')->with($id)->andReturn($documentFileSet);

        $this->logger->expects('warning');

        $this->handler->__invoke(
            new ProcessDocumentFileSetUpdatesCommand($id),
        );
    }
}
