<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload\Process;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\FileInfo;
use App\Domain\Upload\Process\FileProcessException;
use App\Domain\Upload\Process\FileStorer;
use App\Domain\Upload\UploadedFile;
use App\Service\Storage\EntityStorageService;
use App\Tests\Unit\UnitTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;

final class FileStorerTest extends UnitTestCase
{
    private LoggerInterface&MockInterface $logger;
    private EntityManagerInterface&MockInterface $doctrine;
    private EntityStorageService&MockInterface $entityStorageService;
    private FileInfo&MockInterface $fileInfo;
    private UploadedFile&MockInterface $file;
    private Document&MockInterface $document;
    private FileStorer $fileStorer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->doctrine = \Mockery::mock(EntityManagerInterface::class);
        $this->entityStorageService = \Mockery::mock(EntityStorageService::class);
        $this->fileInfo = \Mockery::mock(FileInfo::class);
        $this->file = \Mockery::mock(UploadedFile::class);
        $this->document = \Mockery::mock(Document::class);
        $this->document->shouldReceive('getFileInfo')->andReturn($this->fileInfo);

        $this->fileStorer = new FileStorer(
            $this->logger,
            $this->doctrine,
            $this->entityStorageService,
        );
    }

    public function testStoreForDocument(): void
    {
        $this->entityStorageService
            ->shouldReceive('storeEntity')
            ->once()
            ->with($this->file, $this->document)
            ->andReturnTrue();

        $this->file
            ->shouldReceive('getOriginalFileExtension')
            ->andReturn('pdf');

        $this->fileInfo
            ->expects('setType')
            ->with('pdf');

        $this->fileInfo
            ->expects('setPageCount')
            ->with(null);

        $this->document
            ->expects('setPageCount')
            ->with(0);

        $this->doctrine->shouldReceive('persist')->once()->with($this->document);
        $this->doctrine->shouldReceive('flush')->once();

        $this->fileStorer->storeForDocument($this->file, $this->document, 'documentId');
    }

    public function testStoreForDocumentWithFailure(): void
    {
        $this->entityStorageService
            ->shouldReceive('storeEntity')
            ->once()
            ->with($this->file, $this->document)
            ->andReturnFalse();

        $this->file->shouldReceive('getPathname')->andReturn($expectedPath = 'path');

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Failed to store document', [
                'documentId' => $expectedDocumentId = 'documentId',
                'path' => $expectedPath,
            ]);

        $this->expectExceptionObject(FileProcessException::forFailingToStoreDocument($this->file, $expectedDocumentId));

        $this->fileStorer->storeForDocument($this->file, $this->document, $expectedDocumentId);
    }
}
