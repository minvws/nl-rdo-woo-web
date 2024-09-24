<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload\Postprocessor\Strategy;

use App\Domain\Ingest\Process\IngestProcessOptions;
use App\Domain\Ingest\Process\SubType\SubTypeIngester;
use App\Domain\Upload\Postprocessor\Strategy\FilePdfStrategy;
use App\Domain\Upload\Process\DocumentNumberExtractor;
use App\Domain\Upload\Process\FileStorer;
use App\Domain\Upload\UploadedFile;
use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\FileInfo;
use App\Repository\DocumentRepository;
use App\Service\HistoryService;
use App\Tests\Unit\UnitTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

final class FilePdfStrategyTest extends UnitTestCase
{
    private EntityManagerInterface&MockInterface $doctrine;
    private LoggerInterface&MockInterface $logger;
    private SubTypeIngester&MockInterface $ingestService;
    private HistoryService&MockInterface $historyService;
    private DocumentNumberExtractor&MockInterface $documentNumberExtractor;
    private FileStorer&MockInterface $fileStorer;
    private UploadedFile&MockInterface $file;
    private Dossier&MockInterface $dossier;
    private DocumentRepository&MockInterface $documentRepository;
    private Document&MockInterface $document;
    private FileInfo&MockInterface $fileInfo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrine = \Mockery::mock(EntityManagerInterface::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->ingestService = \Mockery::mock(SubTypeIngester::class);
        $this->historyService = \Mockery::mock(HistoryService::class);
        $this->documentNumberExtractor = \Mockery::mock(DocumentNumberExtractor::class);
        $this->fileStorer = \Mockery::mock(FileStorer::class);
        $this->file = \Mockery::mock(UploadedFile::class);
        $this->dossier = \Mockery::mock(Dossier::class);
        $this->documentRepository = \Mockery::mock(DocumentRepository::class);
        $this->document = \Mockery::mock(Document::class);
        $this->fileInfo = \Mockery::mock(FileInfo::class);
    }

    public function testProcess(): void
    {
        $this->file
            ->shouldReceive('getOriginalFilename')
            ->andReturn($originalFile = 'originalFile.pdf');

        $this->documentNumberExtractor
            ->shouldReceive('extract')
            ->once()
            ->with($originalFile, $this->dossier)
            ->andReturn($documentId = 'documentId');

        $this->doctrine
            ->shouldReceive('getRepository')
            ->with(Document::class)
            ->andReturn($this->documentRepository);

        $this->documentRepository
            ->shouldReceive('findOneByDossierAndDocumentId')
            ->with($this->dossier, $documentId)
            ->andReturn($this->document);

        $this->document
            ->shouldReceive('shouldBeUploaded')
            ->once()
            ->andReturnTrue();

        $this->document
            ->shouldReceive('getFileInfo')
            ->andReturn($this->fileInfo);

        $this->fileInfo
            ->shouldReceive('isUploaded')
            ->once()
            ->andReturnFalse();

        $this->file
            ->shouldReceive('getOriginalFileExtension')
            ->once()
            ->andReturn($originalExtension = 'pdf');

        $this->fileStorer
            ->shouldReceive('storeForDocument')
            ->with($this->file, $this->document, $documentId, $originalExtension);

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

        $strategy = new FilePdfStrategy(
            $this->doctrine,
            $this->logger,
            $this->ingestService,
            $this->historyService,
            $this->documentNumberExtractor,
            $this->fileStorer,
        );

        $strategy->process($this->file, $this->dossier);
    }

    public function testProcessWhenFailingToFetchDocument(): void
    {
        $this->file
            ->shouldReceive('getOriginalFilename')
            ->andReturn($originalFile = 'originalFile.pdf');

        $this->documentNumberExtractor
            ->shouldReceive('extract')
            ->once()
            ->with($originalFile, $this->dossier)
            ->andReturn($documentId = 'documentId');

        $this->doctrine
            ->shouldReceive('getRepository')
            ->with(Document::class)
            ->andReturn($this->documentRepository);

        $this->documentRepository
            ->shouldReceive('findOneByDossierAndDocumentId')
            ->once()
            ->with($this->dossier, $documentId)
            ->andReturnNull();

        $this->dossier
            ->shouldReceive('getId')
            ->andReturn($dossierId = Uuid::v6());

        $this->logger
            ->shouldReceive('info')
            ->with('Could not find document, skipping processing file', [
                'filename' => $originalFile,
                'documentId' => $documentId,
                'dossierId' => $dossierId,
            ]);

        $strategy = new FilePdfStrategy(
            $this->doctrine,
            $this->logger,
            $this->ingestService,
            $this->historyService,
            $this->documentNumberExtractor,
            $this->fileStorer,
        );

        $strategy->process($this->file, $this->dossier);
    }

    public function testProcessWhenDocumentShouldNotBeUploaded(): void
    {
        $this->file
            ->shouldReceive('getOriginalFilename')
            ->andReturn($originalFile = 'originalFile.pdf');

        $this->documentNumberExtractor
            ->shouldReceive('extract')
            ->once()
            ->with($originalFile, $this->dossier)
            ->andReturn($documentId = 'documentId');

        $this->doctrine
            ->shouldReceive('getRepository')
            ->with(Document::class)
            ->andReturn($this->documentRepository);

        $this->documentRepository
            ->shouldReceive('findOneByDossierAndDocumentId')
            ->with($this->dossier, $documentId)
            ->andReturn($this->document);

        $this->dossier
            ->shouldReceive('getId')
            ->andReturn($dossierId = Uuid::v6());

        $this->document
            ->shouldReceive('shouldBeUploaded')
            ->once()
            ->andReturnFalse();

        $this->logger
            ->shouldReceive('warning')
            ->with(
                sprintf('Document with id "%s" should not be uploaded, skipping it', $documentId),
                [
                    'filename' => $originalFile,
                    'documentId' => $documentId,
                    'dossierId' => $dossierId,
                ],
            );

        $strategy = new FilePdfStrategy(
            $this->doctrine,
            $this->logger,
            $this->ingestService,
            $this->historyService,
            $this->documentNumberExtractor,
            $this->fileStorer,
        );

        $strategy->process($this->file, $this->dossier);
    }

    public function testCanProcessReturnsTrue(): void
    {
        $this->file
            ->shouldReceive('getOriginalFileExtension')
            ->once()
            ->andReturn('pdf');

        $strategy = new FilePdfStrategy(
            $this->doctrine,
            $this->logger,
            $this->ingestService,
            $this->historyService,
            $this->documentNumberExtractor,
            $this->fileStorer,
        );
        $result = $strategy->canProcess($this->file, $this->dossier);

        $this->assertTrue($result);
    }

    public function testCanProcessReturnsFalse(): void
    {
        $this->file
            ->shouldReceive('getOriginalFileExtension')
            ->once()
            ->andReturn('txt');

        $strategy = new FilePdfStrategy(
            $this->doctrine,
            $this->logger,
            $this->ingestService,
            $this->historyService,
            $this->documentNumberExtractor,
            $this->fileStorer,
        );
        $result = $strategy->canProcess($this->file, $this->dossier);

        $this->assertFalse($result);
    }
}
