<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Document;

use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Shared\Domain\Ingest\Process\IngestProcessOptions;
use Shared\Domain\Ingest\Process\SubType\SubTypeIngester;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentFileProcessor;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\FileInfo;
use Shared\Domain\Upload\Process\FileStorer;
use Shared\Domain\Upload\UploadedFile;
use Shared\Service\HistoryService;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentId;
use Symfony\Component\Uid\Uuid;

use function sprintf;

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
    private DocumentFileProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->documentRepository = Mockery::mock(DocumentRepository::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->ingestService = Mockery::mock(SubTypeIngester::class);
        $this->historyService = Mockery::mock(HistoryService::class);
        $this->fileStorer = Mockery::mock(FileStorer::class);
        $this->file = Mockery::mock(UploadedFile::class);
        $this->dossier = Mockery::mock(WooDecision::class);
        $this->document = Mockery::mock(Document::class);
        $this->fileInfo = Mockery::mock(FileInfo::class);

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
        $documentId = DocumentId::create('foo.123');
        $this->documentRepository
            ->expects('findOneByDossierAndDocumentId')
            ->with($this->dossier, $documentId)
            ->andReturn($this->document);

        $this->document
            ->expects('shouldBeUploaded')
            ->andReturnTrue();

        $this->document
            ->expects('isWithdrawn')
            ->andReturnFalse();

        $this->document
            ->expects('getFileInfo')
            ->times(3)
            ->andReturn($this->fileInfo);

        $this->dossier
            ->expects('getStatus')
            ->andReturn(DossierStatus::PUBLISHED);

        $this->fileInfo
            ->expects('isUploaded')
            ->andReturnFalse();

        $this->fileStorer
            ->expects('storeForDocument')
            ->with($this->file, $this->document, $documentId);

        $this->ingestService
            ->expects('ingest')
            ->with(
                $this->document,
                Mockery::on(static fn (IngestProcessOptions $options): bool => $options->forceRefresh()),
            );

        $this->fileInfo
            ->expects('getType')
            ->andReturn($fileInfoType = 'pdf');

        $this->fileInfo
            ->expects('getSize')
            ->andReturn(1024);

        $this->historyService
            ->expects('addDocumentEntry')
            ->with(
                $this->document,
                'document_uploaded',
                [
                    'filetype' => $fileInfoType,
                    'filesize' => '1 KB',
                ],
            );

        $this->processor->process($this->file, $this->dossier, $documentId);
    }

    public function testProcessForWithdrawnDocument(): void
    {
        $documentId = DocumentId::create('foo.123');
        $this->documentRepository
            ->expects('findOneByDossierAndDocumentId')
            ->with($this->dossier, $documentId)
            ->andReturn($this->document);

        $this->document
            ->expects('shouldBeUploaded')
            ->andReturnTrue();

        $this->document
            ->expects('isWithdrawn')
            ->andReturnTrue();

        $this->document
            ->expects('getFileInfo')
            ->times(3)
            ->andReturn($this->fileInfo);

        $this->document
            ->expects('removeWithdrawn');

        $this->documentRepository->expects('save')->with($this->document, true);

        $this->dossier
            ->expects('getStatus')
            ->times(2)
            ->andReturn(DossierStatus::PUBLISHED);

        $this->fileInfo
            ->expects('isUploaded')
            ->andReturnFalse();

        $this->fileStorer
            ->expects('storeForDocument')
            ->with($this->file, $this->document, $documentId);

        $this->ingestService
            ->expects('ingest')
            ->with(
                $this->document,
                Mockery::on(static fn (IngestProcessOptions $options): bool => $options->forceRefresh()),
            );

        $this->fileInfo
            ->expects('getType')
            ->andReturn($fileInfoType = 'pdf');

        $this->fileInfo
            ->expects('getSize')
            ->andReturn(1024);

        $this->historyService
            ->expects('addDocumentEntry')
            ->with(
                $this->document,
                'document_uploaded',
                [
                    'filetype' => $fileInfoType,
                    'filesize' => '1 KB',
                ],
            );

        $this->processor->process($this->file, $this->dossier, $documentId);
    }

    public function testProcessWithGivenFileType(): void
    {
        $documentId = DocumentId::create('foo.123');
        $fileType = 'txt';

        $this->documentRepository
            ->expects('findOneByDossierAndDocumentId')
            ->with($this->dossier, $documentId)
            ->andReturn($this->document);

        $this->document
            ->expects('shouldBeUploaded')
            ->andReturnTrue();

        $this->document
            ->expects('isWithdrawn')
            ->andReturnFalse();

        $this->document
            ->expects('getFileInfo')
            ->times(3)
            ->andReturn($this->fileInfo);

        $this->fileInfo
            ->expects('isUploaded')
            ->andReturnFalse();

        $this->fileStorer
            ->expects('storeForDocument')
            ->with($this->file, $this->document, $documentId);

        $this->ingestService
            ->expects('ingest')
            ->with(
                $this->document,
                Mockery::on(static fn (IngestProcessOptions $options): bool => $options->forceRefresh()),
            );

        $this->fileInfo
            ->shouldNotHaveReceived('getType');

        $this->fileInfo
            ->expects('getSize')
            ->andReturn(1024);

        $this->fileInfo
            ->expects('getType')
            ->andReturn($fileType);

        $this->historyService
            ->expects('addDocumentEntry')
            ->with(
                $this->document,
                'document_uploaded',
                [
                    'filetype' => $fileType,
                    'filesize' => '1 KB',
                ],
            );

        $this->dossier
            ->expects('getStatus')
            ->andReturn(DossierStatus::PUBLISHED);

        $this->processor->process($this->file, $this->dossier, $documentId);
    }

    public function testProcessWhenFailingToFetchDocument(): void
    {
        $documentId = DocumentId::create('foo.123');
        $this->file
            ->expects('getOriginalFilename')
            ->andReturn($originalFile = 'originalFile.pdf');

        $this->documentRepository
            ->expects('findOneByDossierAndDocumentId')
            ->with($this->dossier, $documentId)
            ->andReturnNull();

        $this->dossier
            ->expects('getId')
            ->andReturn($dossierId = Uuid::v6());

        $this->logger
            ->expects('info')
            ->with('Could not find document, skipping processing file', [
                'filename' => $originalFile,
                'documentId' => $documentId,
                'dossierId' => $dossierId,
            ]);

        $this->processor->process($this->file, $this->dossier, $documentId);
    }

    public function testProcessWhenDocumentShouldNotBeUploaded(): void
    {
        $documentId = DocumentId::create('foo.123');
        $this->file
            ->expects('getOriginalFilename')
            ->andReturn($originalFile = 'originalFile.pdf');

        $this->documentRepository
            ->expects('findOneByDossierAndDocumentId')
            ->with($this->dossier, $documentId)
            ->andReturn($this->document);

        $this->dossier
            ->expects('getId')
            ->andReturn($dossierId = Uuid::v6());

        $this->dossier
            ->expects('getStatus')
            ->andReturn(DossierStatus::PUBLISHED);

        $this->document
            ->expects('shouldBeUploaded')
            ->andReturnFalse();

        $this->logger
            ->expects('warning')
            ->with(
                sprintf('Document with id "%s" should not be uploaded, skipping it', $documentId),
                [
                    'filename' => $originalFile,
                    'documentId' => $documentId,
                    'dossierId' => $dossierId,
                ],
            );

        $this->processor->process($this->file, $this->dossier, $documentId);
    }
}
