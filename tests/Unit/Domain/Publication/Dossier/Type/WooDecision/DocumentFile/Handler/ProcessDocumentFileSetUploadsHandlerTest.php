<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Handler;

use Doctrine\Common\Collections\ArrayCollection;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Command\ProcessDocumentFileSetUploadsCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\DocumentFileDispatcher;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileSet;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileUpload;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum\DocumentFileSetStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum\DocumentFileUploadStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Handler\ProcessDocumentFileSetUploadsHandler;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Repository\DocumentFileSetRepository;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class ProcessDocumentFileSetUploadsHandlerTest extends UnitTestCase
{
    private DocumentFileSetRepository&MockInterface $repository;
    private DocumentFileDispatcher&MockInterface $dispatcher;
    private LoggerInterface&MockInterface $logger;
    private ProcessDocumentFileSetUploadsHandler $handler;

    protected function setUp(): void
    {
        $this->dispatcher = \Mockery::mock(DocumentFileDispatcher::class);
        $this->repository = \Mockery::mock(DocumentFileSetRepository::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);

        $this->handler = new ProcessDocumentFileSetUploadsHandler(
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
            ->andReturn(DocumentFileSetStatus::PROCESSING_UPLOADS);

        $uploadA = \Mockery::mock(DocumentFileUpload::class);
        $uploadA->expects('getStatus')->andReturn(DocumentFileUploadStatus::FAILED);

        $uploadB = \Mockery::mock(DocumentFileUpload::class);
        $uploadB->expects('getStatus')->andReturn(DocumentFileUploadStatus::UPLOADED);

        $documentFileSet->expects('getUploads')->andReturn(new ArrayCollection([
            $uploadA,
            $uploadB,
        ]));

        $this->repository->expects('find')->with($id)->andReturn($documentFileSet);

        $this->dispatcher->expects('dispatchProcessDocumentFileUploadCommand')->with($uploadB);

        $this->handler->__invoke(
            new ProcessDocumentFileSetUploadsCommand($id),
        );
    }

    public function testInvokeLogsWarningAndAbortsWhenSetCannotBeLoaded(): void
    {
        $id = Uuid::v6();

        $this->repository->expects('find')->with($id)->andReturnNull();

        $this->logger->expects('warning');

        $this->handler->__invoke(
            new ProcessDocumentFileSetUploadsCommand($id),
        );
    }

    public function testInvokeLogsWarningAndAbortsWhenSetStatusIsNotProcessingUploads(): void
    {
        $id = Uuid::v6();
        $documentFileSet = \Mockery::mock(DocumentFileSet::class);
        $documentFileSet
            ->expects('getStatus')->twice()
            ->andReturn(DocumentFileSetStatus::OPEN_FOR_UPLOADS);
        $documentFileSet
            ->expects('getId')
            ->andReturn($id);

        $this->repository->expects('find')->with($id)->andReturn($documentFileSet);

        $this->logger->expects('warning');

        $this->handler->__invoke(
            new ProcessDocumentFileSetUploadsCommand($id),
        );
    }
}
