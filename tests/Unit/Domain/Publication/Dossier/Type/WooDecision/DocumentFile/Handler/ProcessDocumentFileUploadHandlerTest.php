<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Handler;

use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Command\ProcessDocumentFileUploadCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\DocumentFileService;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileSet;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileUpdate;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileUpload;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum\DocumentFileUploadStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Handler\ProcessDocumentFileUploadHandler;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Repository\DocumentFileUpdateRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Repository\DocumentFileUploadRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Upload\FileType\MimeTypeHelper;
use Shared\Domain\Upload\Preprocessor\FilePreprocessor;
use Shared\Domain\Upload\Process\DocumentNumberExtractor;
use Shared\Domain\Upload\UploadedFile;
use Shared\Service\Storage\EntityStorageService;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Unit\Domain\Upload\IterableToGenerator;
use Shared\Tests\Unit\UnitTestCase;
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
    private MimeTypeHelper&MockInterface $mimeTypeHelper;
    private ProcessDocumentFileUploadHandler $handler;

    protected function setUp(): void
    {
        $this->documentFileUploadRepository = Mockery::mock(DocumentFileUploadRepository::class);
        $this->documentFileUpdateRepository = Mockery::mock(DocumentFileUpdateRepository::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->filePreProcessor = Mockery::mock(FilePreprocessor::class);
        $this->entityStorageService = Mockery::mock(EntityStorageService::class);
        $this->documentNumberExtractor = Mockery::mock(DocumentNumberExtractor::class);
        $this->documentFileService = Mockery::mock(DocumentFileService::class);
        $this->mimeTypeHelper = Mockery::mock(MimeTypeHelper::class);

        $this->handler = new ProcessDocumentFileUploadHandler(
            $this->documentFileUploadRepository,
            $this->documentFileUpdateRepository,
            $this->logger,
            $this->filePreProcessor,
            $this->entityStorageService,
            $this->documentNumberExtractor,
            $this->documentFileService,
            $this->mimeTypeHelper,
        );

        parent::setUp();
    }

    public function testInvokeSuccessfully(): void
    {
        $wooDecision = Mockery::mock(WooDecision::class);
        $wooDecision->shouldReceive('getId')->andReturn(Uuid::v6());
        $wooDecision->shouldReceive('getStatus')->andReturn(DossierStatus::PUBLISHED);

        $documentFileSet = Mockery::mock(DocumentFileSet::class);
        $documentFileSet
            ->shouldReceive('getDossier')
            ->andReturn($wooDecision);

        $id = Uuid::v6();
        $upload = Mockery::mock(DocumentFileUpload::class);
        $upload->shouldReceive('getStatus')->andReturn(DocumentFileUploadStatus::UPLOADED);
        $upload->shouldReceive('getDocumentFileSet')->andReturn($documentFileSet);
        $upload->shouldReceive('getFileInfo->getName')->andReturn('upload.zip');
        $upload->expects('getFileInfo->removeFileProperties');

        $this->documentFileUploadRepository->expects('find')->with($id)->andReturn($upload);

        $this->entityStorageService->expects('downloadEntity')->with($upload)->andReturn($localFile = '/foo/bar.baz');

        $fileIterator = $this->iterableToGenerator([
            $fileA = new UploadedFile('tmp/foo', '123.pdf'),
            $fileB = new UploadedFile('tmp/bar', '456.pdf'),
        ]);
        $this->filePreProcessor->expects('process')->andReturn($fileIterator);

        $document = Mockery::mock(Document::class);
        $document->shouldReceive('isWithdrawn')->andReturnFalse();
        $document->shouldReceive('isUploaded')->andReturnFalse();
        $document->shouldReceive('shouldBeUploaded')->with(true)->andReturnTrue();

        $this->mimeTypeHelper
            ->expects('detectMimeTypeFromPath')
            ->with($fileA)
            ->andReturn('application/pdf');

        $this->mimeTypeHelper
            ->expects('detectMimeTypeFromPath')
            ->with($fileB)
            ->andReturn('application/pdf');

        $this->mimeTypeHelper
            ->expects('isValidForUploadGroup')
            ->with('pdf', 'application/pdf', UploadGroupId::WOO_DECISION_DOCUMENTS)
            ->andReturnTrue();

        $this->mimeTypeHelper
            ->expects('isValidForUploadGroup')
            ->with('pdf', 'application/pdf', UploadGroupId::WOO_DECISION_DOCUMENTS)
            ->andReturnTrue();

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
            Mockery::on(static fn (DocumentFileUpdate $update) => $update->getFileInfo()->getName() === '456.pdf'
                && $update->getFileInfo()->getType() === 'pdf'),
            true,
        );

        $this->handler->__invoke(
            new ProcessDocumentFileUploadCommand($id),
        );
    }

    public function testInvokeSkipsDuplicateUpload(): void
    {
        $wooDecision = Mockery::mock(WooDecision::class);
        $wooDecision->shouldReceive('getId')->andReturn(Uuid::v6());

        $documentFileSet = Mockery::mock(DocumentFileSet::class);
        $documentFileSet
            ->shouldReceive('getDossier')
            ->andReturn($wooDecision);

        $id = Uuid::v6();
        $upload = Mockery::mock(DocumentFileUpload::class);
        $upload->shouldReceive('getStatus')->andReturn(DocumentFileUploadStatus::UPLOADED);
        $upload->shouldReceive('getDocumentFileSet')->andReturn($documentFileSet);
        $upload->shouldReceive('getFileInfo->getName')->andReturn('upload.zip');
        $upload->expects('getFileInfo->removeFileProperties');

        $this->documentFileUploadRepository->expects('find')->with($id)->andReturn($upload);

        $this->entityStorageService->expects('downloadEntity')->with($upload)->andReturn($localFile = '/foo/bar.baz');

        $fileIterator = $this->iterableToGenerator([
            $fileA = new UploadedFile('tmp/foo', '123.pdf'),
            $fileB = new UploadedFile('tmp/bar', '456.pdf'),
        ]);
        $this->filePreProcessor->expects('process')->andReturn($fileIterator);

        $document = Mockery::mock(Document::class);
        $document->shouldReceive('isWithdrawn')->andReturnFalse();
        $document->shouldReceive('isUploaded')->andReturnFalse();

        $this->mimeTypeHelper
            ->expects('detectMimeTypeFromPath')
            ->with($fileA)
            ->andReturn('application/pdf');

        $this->mimeTypeHelper
            ->expects('detectMimeTypeFromPath')
            ->with($fileB)
            ->andReturn('application/pdf');

        $this->mimeTypeHelper
            ->expects('isValidForUploadGroup')
            ->with('pdf', 'application/pdf', UploadGroupId::WOO_DECISION_DOCUMENTS)
            ->andReturnTrue();

        $this->mimeTypeHelper
            ->expects('isValidForUploadGroup')
            ->with('pdf', 'application/pdf', UploadGroupId::WOO_DECISION_DOCUMENTS)
            ->andReturnTrue();

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
        $wooDecision = Mockery::mock(WooDecision::class);
        $wooDecision->shouldReceive('getId')->andReturn(Uuid::v6());
        $wooDecision->shouldReceive('getStatus')->andReturn(DossierStatus::PUBLISHED);

        $documentFileSet = Mockery::mock(DocumentFileSet::class);
        $documentFileSet
            ->shouldReceive('getDossier')
            ->andReturn($wooDecision);

        $id = Uuid::v6();
        $upload = Mockery::mock(DocumentFileUpload::class);
        $upload->shouldReceive('getStatus')->andReturn(DocumentFileUploadStatus::UPLOADED);
        $upload->shouldReceive('getDocumentFileSet')->andReturn($documentFileSet);
        $upload->shouldReceive('getFileInfo->getName')->andReturn('upload.zip');
        $upload->expects('getFileInfo->removeFileProperties');

        $this->documentFileUploadRepository->expects('find')->with($id)->andReturn($upload);

        $this->entityStorageService->expects('downloadEntity')->with($upload)->andReturn($localFile = '/foo/bar.baz');

        $fileIterator = $this->iterableToGenerator([
            $fileA = new UploadedFile('tmp/foo', '123.pdf'),
            $fileB = new UploadedFile('tmp/bar', '456.pdf'),
        ]);
        $this->filePreProcessor->expects('process')->andReturn($fileIterator);

        $document = Mockery::mock(Document::class);
        $document->shouldReceive('isWithdrawn')->andReturnFalse();
        $document->shouldReceive('isUploaded')->andReturnFalse();
        $document->shouldReceive('shouldBeUploaded')->with(true)->andReturnFalse();

        $this->mimeTypeHelper
            ->expects('detectMimeTypeFromPath')
            ->with($fileA)
            ->andReturn('application/pdf');

        $this->mimeTypeHelper
            ->expects('detectMimeTypeFromPath')
            ->with($fileB)
            ->andReturn('application/pdf');

        $this->mimeTypeHelper
            ->expects('isValidForUploadGroup')
            ->with('pdf', 'application/pdf', UploadGroupId::WOO_DECISION_DOCUMENTS)
            ->andReturnTrue();

        $this->mimeTypeHelper
            ->expects('isValidForUploadGroup')
            ->with('pdf', 'application/pdf', UploadGroupId::WOO_DECISION_DOCUMENTS)
            ->andReturnTrue();

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
        $upload = Mockery::mock(DocumentFileUpload::class);
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

        $upload = Mockery::mock(DocumentFileUpload::class);
        $upload->shouldReceive('getStatus')->andReturn(DocumentFileUploadStatus::UPLOADED);

        $this->documentFileUploadRepository->expects('find')->with($id)->andReturn($upload);

        $this->entityStorageService->expects('downloadEntity')->with($upload)->andReturnFalse();

        $this->logger->expects('warning');

        $this->handler->__invoke(
            new ProcessDocumentFileUploadCommand($id),
        );
    }

    public function testInvokeSkipsFileWithUnsupportedMimeType(): void
    {
        $wooDecision = Mockery::mock(WooDecision::class);
        $wooDecision->shouldReceive('getId')->andReturn(Uuid::v6());
        $wooDecision->shouldReceive('getStatus')->andReturn(DossierStatus::PUBLISHED);

        $documentFileSet = Mockery::mock(DocumentFileSet::class);
        $documentFileSet
            ->shouldReceive('getDossier')
            ->andReturn($wooDecision);

        $id = Uuid::v6();
        $upload = Mockery::mock(DocumentFileUpload::class);
        $upload->shouldReceive('getStatus')->andReturn(DocumentFileUploadStatus::UPLOADED);
        $upload->shouldReceive('getDocumentFileSet')->andReturn($documentFileSet);
        $upload->shouldReceive('getFileInfo->getName')->andReturn('upload.zip');
        $upload->expects('getFileInfo->removeFileProperties');

        $this->documentFileUploadRepository->expects('find')->with($id)->andReturn($upload);

        $this->entityStorageService->expects('downloadEntity')->with($upload)->andReturn($localFile = '/foo/bar.baz');

        $fileIterator = $this->iterableToGenerator([
            $fileA = new UploadedFile('tmp/foo', '123.pdf'),
            $fileB = new UploadedFile('tmp/bar', '456.pdf'),
        ]);
        $this->filePreProcessor->expects('process')->andReturn($fileIterator);

        $document = Mockery::mock(Document::class);
        $document->shouldReceive('isWithdrawn')->andReturnFalse();
        $document->shouldReceive('isUploaded')->andReturnFalse();
        $document->shouldReceive('shouldBeUploaded')->with(true)->andReturnFalse();

        $this->mimeTypeHelper
            ->expects('detectMimeTypeFromPath')
            ->with($fileA)
            ->andReturn('application/pdf');

        $this->mimeTypeHelper
            ->expects('detectMimeTypeFromPath')
            ->with($fileB)
            ->andReturn('application/pdf');

        $this->mimeTypeHelper
            ->expects('isValidForUploadGroup')
            ->with('pdf', 'application/pdf', UploadGroupId::WOO_DECISION_DOCUMENTS)
            ->andReturnTrue();

        $this->mimeTypeHelper
            ->expects('isValidForUploadGroup')
            ->with('pdf', 'application/pdf', UploadGroupId::WOO_DECISION_DOCUMENTS)
            ->andReturnFalse();

        $this->documentNumberExtractor->expects('matchDocumentForFile')->with($fileA, $wooDecision)->andReturnNull();

        $upload->expects('markAsProcessed');
        $this->documentFileUploadRepository->expects('save')->with($upload, true);

        $this->documentFileService->expects('checkProcessingUploadsCompletion')->with($documentFileSet);

        $this->entityStorageService->expects('deleteAllFilesForEntity')->with($upload);
        $this->entityStorageService->expects('removeDownload')->with($localFile);

        $this->logger->expects('info');

        $this->handler->__invoke(
            new ProcessDocumentFileUploadCommand($id),
        );
    }
}
