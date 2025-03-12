<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Handler;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Command\ProcessDocumentFileUploadCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\DocumentFileService;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileSet;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileUpdate;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileUpload;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum\DocumentFileUploadStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Handler\ProcessDocumentFileUploadHandler;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Repository\DocumentFileUpdateRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Repository\DocumentFileUploadRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Upload\Preprocessor\FilePreprocessor;
use App\Domain\Upload\Process\DocumentNumberExtractor;
use App\Domain\Upload\UploadedFile;
use App\Service\Storage\EntityStorageService;
use App\Tests\Unit\Domain\Upload\IterableToGenerator;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

class ProcessDocumentFileUploadHandlerTest extends UnitTestCase
{
    use IterableToGenerator;

    private DocumentFileUploadRepository&MockInterface $documentFileUploadRepository;
    private DocumentFileUpdateRepository&MockInterface $documentFileUpdateRepository;
    private LoggerInterface&MockInterface $logger;
    private FilePreprocessor&MockInterface $filePreProcessor;
    private EntityStorageService&MockInterface $entityStorageService;
    private DocumentNumberExtractor&MockInterface $documentNumberExtractor;
    private DocumentFileService&MockInterface $documentFileService;
    private ProcessDocumentFileUploadHandler $handler;

    public function setUp(): void
    {
        $this->documentFileUploadRepository = \Mockery::mock(DocumentFileUploadRepository::class);
        $this->documentFileUpdateRepository = \Mockery::mock(DocumentFileUpdateRepository::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->filePreProcessor = \Mockery::mock(FilePreprocessor::class);
        $this->entityStorageService = \Mockery::mock(EntityStorageService::class);
        $this->documentNumberExtractor = \Mockery::mock(DocumentNumberExtractor::class);
        $this->documentFileService = \Mockery::mock(DocumentFileService::class);

        $this->handler = new ProcessDocumentFileUploadHandler(
            $this->documentFileUploadRepository,
            $this->documentFileUpdateRepository,
            $this->logger,
            $this->filePreProcessor,
            $this->entityStorageService,
            $this->documentNumberExtractor,
            $this->documentFileService,
        );

        parent::setUp();
    }

    public function testInvokeSuccessfully(): void
    {
        $wooDecision = \Mockery::mock(WooDecision::class);
        $wooDecision->shouldReceive('getId')->andReturn(Uuid::v6());
        $wooDecision->shouldReceive('getStatus')->andReturn(DossierStatus::PUBLISHED);

        $documentFileSet = \Mockery::mock(DocumentFileSet::class);
        $documentFileSet
            ->shouldReceive('getDossier')
            ->andReturn($wooDecision);

        $id = Uuid::v6();
        $upload = \Mockery::mock(DocumentFileUpload::class);
        $upload->shouldReceive('getStatus')->andReturn(DocumentFileUploadStatus::UPLOADED);
        $upload->shouldReceive('getDocumentFileSet')->andReturn($documentFileSet);
        $upload->shouldReceive('getFileInfo->getName')->andReturn('upload.zip');

        $this->documentFileUploadRepository->expects('find')->with($id)->andReturn($upload);

        $this->entityStorageService->expects('downloadEntity')->with($upload)->andReturn($localFile = '/foo/bar.baz');

        $fileIterator = $this->iterableToGenerator([
            $fileA = new UploadedFile('tmp/foo', '123.pdf'),
            $fileB = new UploadedFile('tmp/bar', '456.pdf'),
        ]);
        $this->filePreProcessor->expects('process')->andReturn($fileIterator);

        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('isWithdrawn')->andReturnFalse();
        $document->shouldReceive('isUploaded')->andReturnFalse();
        $document->shouldReceive('shouldBeUploaded')->with(true)->andReturnTrue();

        $this->documentNumberExtractor->expects('matchDocumentForFile')->with($fileA, $wooDecision)->andReturnNull();

        $this->documentNumberExtractor->expects('matchDocumentForFile')->with($fileB, $wooDecision)->andReturn($document);
        $this->entityStorageService->expects('storeEntity');

        $upload->expects('markAsProcessed');
        $this->documentFileUploadRepository->expects('save')->with($upload, true);

        $this->documentFileService->expects('checkProcessingUploadsCompletion')->with($documentFileSet);

        $this->entityStorageService->expects('deleteAllFilesForEntity')->with($upload);
        $this->entityStorageService->expects('removeDownload')->with($localFile);

        $this->documentFileUpdateRepository
            ->expects('hasUpdateForFileSetAndDocument')
            ->with($documentFileSet, $document)
            ->andReturnFalse();

        $this->documentFileUpdateRepository->expects('save')->with(
            \Mockery::on(static function (DocumentFileUpdate $update) {
                return $update->getFileInfo()->getName() === '456.pdf'
                    && $update->getFileInfo()->getType() === 'pdf';
            }),
            true,
        );

        $this->handler->__invoke(
            new ProcessDocumentFileUploadCommand($id),
        );
    }

    public function testInvokeSkipsDuplicateUpload(): void
    {
        $wooDecision = \Mockery::mock(WooDecision::class);
        $wooDecision->shouldReceive('getId')->andReturn(Uuid::v6());

        $documentFileSet = \Mockery::mock(DocumentFileSet::class);
        $documentFileSet
            ->shouldReceive('getDossier')
            ->andReturn($wooDecision);

        $id = Uuid::v6();
        $upload = \Mockery::mock(DocumentFileUpload::class);
        $upload->shouldReceive('getStatus')->andReturn(DocumentFileUploadStatus::UPLOADED);
        $upload->shouldReceive('getDocumentFileSet')->andReturn($documentFileSet);
        $upload->shouldReceive('getFileInfo->getName')->andReturn('upload.zip');

        $this->documentFileUploadRepository->expects('find')->with($id)->andReturn($upload);

        $this->entityStorageService->expects('downloadEntity')->with($upload)->andReturn($localFile = '/foo/bar.baz');

        $fileIterator = $this->iterableToGenerator([
            $fileA = new UploadedFile('tmp/foo', '123.pdf'),
            $fileB = new UploadedFile('tmp/bar', '456.pdf'),
        ]);
        $this->filePreProcessor->expects('process')->andReturn($fileIterator);

        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('isWithdrawn')->andReturnFalse();
        $document->shouldReceive('isUploaded')->andReturnFalse();

        $this->documentNumberExtractor->expects('matchDocumentForFile')->with($fileA, $wooDecision)->andReturnNull();
        $this->documentNumberExtractor->expects('matchDocumentForFile')->with($fileB, $wooDecision)->andReturn($document);

        $upload->expects('markAsProcessed');
        $this->documentFileUploadRepository->expects('save')->with($upload, true);

        $this->documentFileService->expects('checkProcessingUploadsCompletion')->with($documentFileSet);

        $this->entityStorageService->expects('deleteAllFilesForEntity')->with($upload);
        $this->entityStorageService->expects('removeDownload')->with($localFile);

        $this->documentFileUpdateRepository
            ->expects('hasUpdateForFileSetAndDocument')
            ->with($documentFileSet, $document)
            ->andReturnTrue();

        $this->logger->expects('info');

        $this->handler->__invoke(
            new ProcessDocumentFileUploadCommand($id),
        );
    }

    public function testInvokeSkipsFileThatShouldNotBeUploaded(): void
    {
        $wooDecision = \Mockery::mock(WooDecision::class);
        $wooDecision->shouldReceive('getId')->andReturn(Uuid::v6());
        $wooDecision->shouldReceive('getStatus')->andReturn(DossierStatus::PUBLISHED);

        $documentFileSet = \Mockery::mock(DocumentFileSet::class);
        $documentFileSet
            ->shouldReceive('getDossier')
            ->andReturn($wooDecision);

        $id = Uuid::v6();
        $upload = \Mockery::mock(DocumentFileUpload::class);
        $upload->shouldReceive('getStatus')->andReturn(DocumentFileUploadStatus::UPLOADED);
        $upload->shouldReceive('getDocumentFileSet')->andReturn($documentFileSet);
        $upload->shouldReceive('getFileInfo->getName')->andReturn('upload.zip');

        $this->documentFileUploadRepository->expects('find')->with($id)->andReturn($upload);

        $this->entityStorageService->expects('downloadEntity')->with($upload)->andReturn($localFile = '/foo/bar.baz');

        $fileIterator = $this->iterableToGenerator([
            $fileA = new UploadedFile('tmp/foo', '123.pdf'),
            $fileB = new UploadedFile('tmp/bar', '456.pdf'),
        ]);
        $this->filePreProcessor->expects('process')->andReturn($fileIterator);

        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('isWithdrawn')->andReturnFalse();
        $document->shouldReceive('isUploaded')->andReturnFalse();
        $document->shouldReceive('shouldBeUploaded')->with(true)->andReturnFalse();

        $this->documentNumberExtractor->expects('matchDocumentForFile')->with($fileA, $wooDecision)->andReturnNull();
        $this->documentNumberExtractor->expects('matchDocumentForFile')->with($fileB, $wooDecision)->andReturn($document);

        $upload->expects('markAsProcessed');
        $this->documentFileUploadRepository->expects('save')->with($upload, true);

        $this->documentFileService->expects('checkProcessingUploadsCompletion')->with($documentFileSet);

        $this->entityStorageService->expects('deleteAllFilesForEntity')->with($upload);
        $this->entityStorageService->expects('removeDownload')->with($localFile);

        $this->documentFileUpdateRepository
            ->expects('hasUpdateForFileSetAndDocument')
            ->with($documentFileSet, $document)
            ->andReturnFalse();

        $this->logger->expects('info');

        $this->handler->__invoke(
            new ProcessDocumentFileUploadCommand($id),
        );
    }

    public function testInvokeSkipsFailedUpload(): void
    {
        $id = Uuid::v6();
        $upload = \Mockery::mock(DocumentFileUpload::class);
        $upload->shouldReceive('getStatus')->andReturn(DocumentFileUploadStatus::FAILED);

        $this->documentFileUploadRepository->expects('find')->with($id)->andReturn($upload);

        $this->handler->__invoke(
            new ProcessDocumentFileUploadCommand($id),
        );
    }

    public function testInvokeLogsWarningAndAbortsWhenDocumentFileUploadEntityCannotBeLoaded(): void
    {
        $id = Uuid::v6();

        $this->documentFileUploadRepository->expects('find')->with($id)->andReturnNull();

        $this->logger->expects('warning');

        $this->handler->__invoke(
            new ProcessDocumentFileUploadCommand($id),
        );
    }

    public function testInvokeLogsWarningAndAbortsWhenUploadCannotBeDownloaded(): void
    {
        $id = Uuid::v6();

        $upload = \Mockery::mock(DocumentFileUpload::class);
        $upload->shouldReceive('getStatus')->andReturn(DocumentFileUploadStatus::UPLOADED);

        $this->documentFileUploadRepository->expects('find')->with($id)->andReturn($upload);

        $this->entityStorageService->expects('downloadEntity')->with($upload)->andReturnFalse();

        $this->logger->expects('warning');

        $this->handler->__invoke(
            new ProcessDocumentFileUploadCommand($id),
        );
    }
}
