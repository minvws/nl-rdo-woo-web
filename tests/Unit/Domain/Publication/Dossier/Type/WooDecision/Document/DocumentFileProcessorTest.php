<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Document;

use App\Domain\Ingest\Process\IngestProcessOptions;
use App\Domain\Ingest\Process\SubType\SubTypeIngester;
use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentFileProcessor;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\FileInfo;
use App\Domain\Upload\Process\FileStorer;
use App\Domain\Upload\UploadedFile;
use App\Service\HistoryService;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

final class DocumentFileProcessorTest extends UnitTestCase
{
    private LoggerInterface&MockInterface $logger;
    private SubTypeIngester&MockInterface $ingestService;
    private HistoryService&MockInterface $historyService;
    private FileStorer&MockInterface $fileStorer;
    private UploadedFile&MockInterface $file;
    private WooDecision&MockInterface $dossier;
    private DocumentRepository&MockInterface $documentRepository;
    private Document&MockInterface $document;
    private FileInfo&MockInterface $fileInfo;
    private string $documentId = 'documentId';
    private DocumentFileProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->documentRepository = \Mockery::mock(DocumentRepository::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->ingestService = \Mockery::mock(SubTypeIngester::class);
        $this->historyService = \Mockery::mock(HistoryService::class);
        $this->fileStorer = \Mockery::mock(FileStorer::class);
        $this->file = \Mockery::mock(UploadedFile::class);
        $this->dossier = \Mockery::mock(WooDecision::class);
        $this->document = \Mockery::mock(Document::class);
        $this->fileInfo = \Mockery::mock(FileInfo::class);

        $this->processor = new DocumentFileProcessor(
            $this->documentRepository,
            $this->logger,
            $this->ingestService,
            $this->historyService,
            $this->fileStorer,
        );
    }

    public function testProcess(): void
    {
        $this->documentRepository
            ->shouldReceive('findOneByDossierAndDocumentId')
            ->with($this->dossier, $this->documentId)
            ->andReturn($this->document);

        $this->document
            ->shouldReceive('shouldBeUploaded')
            ->once()
            ->andReturnTrue();

        $this->document
            ->shouldReceive('isWithdrawn')
            ->andReturnFalse();

        $this->document
            ->shouldReceive('getFileInfo')
            ->andReturn($this->fileInfo);

        $this->dossier
            ->shouldReceive('getStatus')
            ->andReturn(DossierStatus::PUBLISHED);

        $this->fileInfo
            ->shouldReceive('isUploaded')
            ->once()
            ->andReturnFalse();

        $this->fileStorer
            ->shouldReceive('storeForDocument')
            ->with($this->file, $this->document, $this->documentId);

        $this->ingestService
            ->shouldReceive('ingest')
            ->with(
                $this->document,
                \Mockery::on(fn (IngestProcessOptions $options): bool => $options->forceRefresh()),
            );

        $this->fileInfo
            ->shouldReceive('getType')
            ->andReturn($fileInfoType = 'pdf');

        $this->fileInfo
            ->shouldReceive('getSize')
            ->andReturn(1024);

        $this->historyService
            ->shouldReceive('addDocumentEntry')
            ->with(
                $this->document,
                'document_uploaded',
                [
                    'filetype' => $fileInfoType,
                    'filesize' => '1 KB',
                ],
            );

        $this->processor->process($this->file, $this->dossier, $this->documentId);
    }

    public function testProcessForWithdrawnDocument(): void
    {
        $this->documentRepository
            ->shouldReceive('findOneByDossierAndDocumentId')
            ->with($this->dossier, $this->documentId)
            ->andReturn($this->document);

        $this->document
            ->shouldReceive('shouldBeUploaded')
            ->once()
            ->andReturnTrue();

        $this->document
            ->shouldReceive('isWithdrawn')
            ->andReturnTrue();

        $this->document
            ->shouldReceive('getFileInfo')
            ->andReturn($this->fileInfo);

        $this->document
            ->expects('removeWithdrawn');

        $this->documentRepository->expects('save')->with($this->document, true);

        $this->dossier
            ->shouldReceive('getStatus')
            ->andReturn(DossierStatus::PUBLISHED);

        $this->fileInfo
            ->shouldReceive('isUploaded')
            ->once()
            ->andReturnFalse();

        $this->fileStorer
            ->shouldReceive('storeForDocument')
            ->with($this->file, $this->document, $this->documentId);

        $this->ingestService
            ->shouldReceive('ingest')
            ->with(
                $this->document,
                \Mockery::on(fn (IngestProcessOptions $options): bool => $options->forceRefresh()),
            );

        $this->fileInfo
            ->shouldReceive('getType')
            ->andReturn($fileInfoType = 'pdf');

        $this->fileInfo
            ->shouldReceive('getSize')
            ->andReturn(1024);

        $this->historyService
            ->shouldReceive('addDocumentEntry')
            ->with(
                $this->document,
                'document_uploaded',
                [
                    'filetype' => $fileInfoType,
                    'filesize' => '1 KB',
                ],
            );

        $this->processor->process($this->file, $this->dossier, $this->documentId);
    }

    public function testProcessWithGivenFileType(): void
    {
        $fileType = 'txt';

        $this->documentRepository
            ->shouldReceive('findOneByDossierAndDocumentId')
            ->with($this->dossier, $this->documentId)
            ->andReturn($this->document);

        $this->document
            ->shouldReceive('shouldBeUploaded')
            ->once()
            ->andReturnTrue();

        $this->document
            ->shouldReceive('isWithdrawn')
            ->andReturnFalse();

        $this->document
            ->shouldReceive('getFileInfo')
            ->andReturn($this->fileInfo);

        $this->fileInfo
            ->shouldReceive('isUploaded')
            ->once()
            ->andReturnFalse();

        $this->fileStorer
            ->shouldReceive('storeForDocument')
            ->with($this->file, $this->document, $this->documentId);

        $this->ingestService
            ->shouldReceive('ingest')
            ->with(
                $this->document,
                \Mockery::on(fn (IngestProcessOptions $options): bool => $options->forceRefresh()),
            );

        $this->fileInfo
            ->shouldNotHaveReceived('getType');

        $this->fileInfo
            ->shouldReceive('getSize')
            ->andReturn(1024);

        $this->fileInfo
            ->shouldReceive('getType')
            ->andReturn($fileType);

        $this->historyService
            ->shouldReceive('addDocumentEntry')
            ->with(
                $this->document,
                'document_uploaded',
                [
                    'filetype' => $fileType,
                    'filesize' => '1 KB',
                ],
            );

        $this->dossier
            ->shouldReceive('getStatus')
            ->andReturn(DossierStatus::PUBLISHED);

        $this->processor->process($this->file, $this->dossier, $this->documentId);
    }

    public function testProcessWhenFailingToFetchDocument(): void
    {
        $this->file
            ->shouldReceive('getOriginalFilename')
            ->andReturn($originalFile = 'originalFile.pdf');

        $this->documentRepository
            ->shouldReceive('findOneByDossierAndDocumentId')
            ->once()
            ->with($this->dossier, $this->documentId)
            ->andReturnNull();

        $this->dossier
            ->shouldReceive('getId')
            ->andReturn($dossierId = Uuid::v6());

        $this->logger
            ->shouldReceive('info')
            ->with('Could not find document, skipping processing file', [
                'filename' => $originalFile,
                'documentId' => $this->documentId,
                'dossierId' => $dossierId,
            ]);

        $this->processor->process($this->file, $this->dossier, $this->documentId);
    }

    public function testProcessWhenDocumentShouldNotBeUploaded(): void
    {
        $this->file
            ->shouldReceive('getOriginalFilename')
            ->andReturn($originalFile = 'originalFile.pdf');

        $this->documentRepository
            ->shouldReceive('findOneByDossierAndDocumentId')
            ->with($this->dossier, $this->documentId)
            ->andReturn($this->document);

        $this->dossier
            ->shouldReceive('getId')
            ->andReturn($dossierId = Uuid::v6());

        $this->dossier
            ->shouldReceive('getStatus')
            ->andReturn(DossierStatus::PUBLISHED);

        $this->document
            ->shouldReceive('shouldBeUploaded')
            ->once()
            ->andReturnFalse();

        $this->logger
            ->shouldReceive('warning')
            ->with(
                sprintf('Document with id "%s" should not be uploaded, skipping it', $this->documentId),
                [
                    'filename' => $originalFile,
                    'documentId' => $this->documentId,
                    'dossierId' => $dossierId,
                ],
            );

        $this->processor->process($this->file, $this->dossier, $this->documentId);
    }
}
