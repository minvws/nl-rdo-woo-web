<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Handler;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentFileProcessor;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Command\ProcessDocumentFileUpdateCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\DocumentFileService;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileSet;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileUpdate;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum\DocumentFileUpdateStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Handler\ProcessDocumentFileUpdateHandler;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Repository\DocumentFileUpdateRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Upload\UploadedFile;
use App\Service\Storage\EntityStorageService;
use App\Tests\Unit\Domain\Upload\IterableToGenerator;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

class ProcessDocumentFileUpdateHandlerTest extends UnitTestCase
{
    use IterableToGenerator;

    private DocumentFileUpdateRepository&MockInterface $documentFileUpdateRepository;
    private LoggerInterface&MockInterface $logger;
    private EntityStorageService&MockInterface $entityStorageService;
    private DocumentFileService&MockInterface $documentFileService;
    private DocumentFileProcessor&MockInterface $fileProcessor;
    private ProcessDocumentFileUpdateHandler $handler;

    public function setUp(): void
    {
        $this->documentFileUpdateRepository = \Mockery::mock(DocumentFileUpdateRepository::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->entityStorageService = \Mockery::mock(EntityStorageService::class);
        $this->documentFileService = \Mockery::mock(DocumentFileService::class);
        $this->fileProcessor = \Mockery::mock(DocumentFileProcessor::class);

        $this->handler = new ProcessDocumentFileUpdateHandler(
            $this->documentFileUpdateRepository,
            $this->logger,
            $this->entityStorageService,
            $this->documentFileService,
            $this->fileProcessor,
        );

        parent::setUp();
    }

    public function testInvokeSuccessfully(): void
    {
        $wooDecision = \Mockery::mock(WooDecision::class);
        $wooDecision->shouldReceive('getId')->andReturn(Uuid::v6());

        $document = \Mockery::mock(Document::class);
        $document
            ->shouldReceive('getDocumentId')
            ->andReturn($documentId = 'foo-123');

        $documentFileSet = \Mockery::mock(DocumentFileSet::class);
        $documentFileSet
            ->shouldReceive('getDossier')
            ->andReturn($wooDecision);

        $id = Uuid::v6();

        $update = \Mockery::mock(DocumentFileUpdate::class);
        $update
            ->shouldReceive('getStatus')
            ->andReturn(DocumentFileUpdateStatus::PENDING);
        $update
            ->shouldReceive('getDocumentFileSet')
            ->andReturn($documentFileSet);
        $update
            ->shouldReceive('getDocument')
            ->andReturn($document);

        $update
            ->shouldReceive('getFileInfo->getName')
            ->andReturn('foo.bar');

        $update->expects('getFileInfo->removeFileProperties');

        $this->documentFileUpdateRepository
            ->expects('find')
            ->with($id)
            ->andReturn($update);

        $this->entityStorageService
            ->expects('downloadEntity')
            ->with($update)
            ->andReturn($localPath = '/foo/bar.baz');

        $this->fileProcessor
            ->expects('process')
            ->with(\Mockery::type(UploadedFile::class), $wooDecision, $documentId);

        $update->expects('setStatus')->with(DocumentFileUpdateStatus::COMPLETED);
        $this->documentFileUpdateRepository->expects('save')->with($update, true);

        $this->documentFileService->expects('checkProcessingUpdatesCompletion')->with($documentFileSet);

        $this->entityStorageService->expects('deleteAllFilesForEntity')->with($update);
        $this->entityStorageService->expects('removeDownload')->with($localPath, true);

        $this->handler->__invoke(
            new ProcessDocumentFileUpdateCommand($id),
        );
    }

    public function testInvokeLogsWarningAndAbortsWhenDocumentFileUpdateEntityCannotBeLoaded(): void
    {
        $id = Uuid::v6();

        $this->documentFileUpdateRepository->expects('find')->with($id)->andReturnNull();

        $this->logger->expects('warning');

        $this->handler->__invoke(
            new ProcessDocumentFileUpdateCommand($id),
        );
    }

    public function testInvokeLogsWarningAndAbortsWhenUpdateFileCannotBeDownloaded(): void
    {
        $id = Uuid::v6();

        $update = \Mockery::mock(DocumentFileUpdate::class);
        $update->shouldReceive('getStatus')->andReturn(DocumentFileUpdateStatus::PENDING);

        $this->documentFileUpdateRepository->expects('find')->with($id)->andReturn($update);

        $this->entityStorageService->expects('downloadEntity')->with($update)->andReturnFalse();

        $this->logger->expects('warning');

        $this->handler->__invoke(
            new ProcessDocumentFileUpdateCommand($id),
        );
    }
}
