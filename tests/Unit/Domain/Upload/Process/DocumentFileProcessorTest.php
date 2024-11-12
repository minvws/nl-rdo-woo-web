<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload\Process;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Upload\Process\DocumentFileProcessor;
use App\Domain\Upload\Process\DocumentNumberExtractor;
use App\Domain\Upload\Process\FileStorer;
use App\Domain\Upload\UploadedFile;
use App\Entity\Document;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

final class DocumentFileProcessorTest extends UnitTestCase
{
    private LoggerInterface&MockInterface $logger;
    private DocumentNumberExtractor&MockInterface $documentNumberExtractor;
    private FileStorer&MockInterface $fileStorer;
    private UploadedFile&MockInterface $file;
    private WooDecision&MockInterface $dossier;
    private Document&MockInterface $document;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->documentNumberExtractor = \Mockery::mock(DocumentNumberExtractor::class);
        $this->fileStorer = \Mockery::mock(FileStorer::class);
        $this->file = \Mockery::mock(UploadedFile::class);
        $this->dossier = \Mockery::mock(WooDecision::class);
        $this->document = \Mockery::mock(Document::class);
    }

    public function testProcess(): void
    {
        $this->file
            ->shouldReceive('getOriginalFilename')
            ->andReturn($originalFile = 'originalFile');

        $this->documentNumberExtractor
            ->shouldReceive('extract')
            ->once()
            ->with($originalFile, $this->dossier)
            ->andReturn($documentId = 'documentId');

        $this->document
            ->shouldReceive('getDocumentId')
            ->once()
            ->andReturn($documentId);

        $this->fileStorer
            ->shouldReceive('storeForDocument')
            ->once()
            ->with(
                $this->file,
                $this->document,
                $documentId,
                $type = 'type',
            );

        $documentFileProcessor = new DocumentFileProcessor(
            $this->logger,
            $this->documentNumberExtractor,
            $this->fileStorer,
        );
        $documentFileProcessor->process(
            $this->file,
            $this->dossier,
            $this->document,
            $type,
        );
    }

    public function testProcessWithNonMatchingDocumentId(): void
    {
        $this->file
            ->shouldReceive('getOriginalFilename')
            ->andReturn($originalFile = 'originalFile');

        $this->documentNumberExtractor
            ->shouldReceive('extract')
            ->once()
            ->with($originalFile, $this->dossier)
            ->andReturn($extractedDocumentId = 'differentDocumentId');

        $this->document
            ->shouldReceive('getDocumentId')
            ->once()
            ->andReturn('documentId');

        $this->dossier
            ->shouldReceive('getId')
            ->once()
            ->andReturn($dossierId = Uuid::v6());

        $this->logger
            ->shouldReceive('warning')
            ->once()
            ->with(
                sprintf('Filename does not match the document with id %s', $extractedDocumentId),
                [
                    'filename' => $originalFile,
                    'documentId' => $extractedDocumentId,
                    'dossierId' => $dossierId,
                ],
            );

        $documentFileProcessor = new DocumentFileProcessor(
            $this->logger,
            $this->documentNumberExtractor,
            $this->fileStorer,
        );
        $documentFileProcessor->process(
            $this->file,
            $this->dossier,
            $this->document,
            'type',
        );
    }
}
