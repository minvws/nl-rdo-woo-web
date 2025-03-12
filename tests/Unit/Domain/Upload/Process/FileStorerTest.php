<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload\Process;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\FileInfo;
use App\Domain\Upload\Process\FileProcessException;
use App\Domain\Upload\Process\FileStorer;
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
    private \SplFileInfo&MockInterface $file;
    private Document&MockInterface $document;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->doctrine = \Mockery::mock(EntityManagerInterface::class);
        $this->entityStorageService = \Mockery::mock(EntityStorageService::class);
        $this->fileInfo = \Mockery::mock(FileInfo::class);
        $this->file = \Mockery::mock(\SplFileInfo::class);
        $this->document = \Mockery::mock(Document::class);
        $this->document->shouldReceive('getFileInfo')->andReturn($this->fileInfo);
    }

    public function testStoreForDocument(): void
    {
        $this->entityStorageService
            ->shouldReceive('storeEntity')
            ->once()
            ->with($this->file, $this->document)
            ->andReturnTrue();

        $this->fileInfo
            ->shouldReceive('setType')
            ->once()
            ->with($expectedType = 'type');

        $this->doctrine->shouldReceive('persist')->once()->with($this->document);
        $this->doctrine->shouldReceive('flush')->once();

        $fileStorer = new FileStorer($this->logger, $this->doctrine, $this->entityStorageService);
        $fileStorer->storeForDocument($this->file, $this->document, 'documentId', $expectedType);
    }

    public function testStoreForDorcumentWithFailure(): void
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

        $fileStorer = new FileStorer($this->logger, $this->doctrine, $this->entityStorageService);
        $fileStorer->storeForDocument($this->file, $this->document, $expectedDocumentId, 'type');
    }
}
