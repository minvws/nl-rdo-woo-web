<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload\Process;

use Doctrine\ORM\EntityManagerInterface;
use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Domain\Publication\FileInfo;
use Shared\Domain\Upload\Process\FileProcessException;
use Shared\Domain\Upload\Process\FileStorer;
use Shared\Service\Storage\EntityStorageService;
use Shared\Service\Storage\ThumbnailStorageService;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentId;

final class FileStorerTest extends UnitTestCase
{
    private LoggerInterface&MockInterface $logger;
    private EntityManagerInterface&MockInterface $doctrine;
    private EntityStorageService&MockInterface $entityStorageService;
    private ThumbnailStorageService&MockInterface $thumbnailStorageService;
    private FileInfo&MockInterface $fileInfo;
    private EntityWithFileInfo&MockInterface $source;
    private Document&MockInterface $document;
    private FileStorer $fileStorer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->doctrine = Mockery::mock(EntityManagerInterface::class);
        $this->entityStorageService = Mockery::mock(EntityStorageService::class);
        $this->thumbnailStorageService = Mockery::mock(ThumbnailStorageService::class);
        $this->fileInfo = Mockery::mock(FileInfo::class);
        $this->source = Mockery::mock(EntityWithFileInfo::class);
        $this->document = Mockery::mock(Document::class);

        $this->fileStorer = new FileStorer(
            $this->logger,
            $this->doctrine,
            $this->entityStorageService,
            $this->thumbnailStorageService,
        );
    }

    public function testStoreForDocumentForFirstUpload(): void
    {
        $documentId = DocumentId::create('foo.123');
        $this->document->expects('getFileInfo')
            ->times(2)
            ->andReturn($this->fileInfo);

        $this->fileInfo
            ->expects('isUploaded')
            ->andReturnFalse();

        $this->entityStorageService
            ->expects('copyEntity')
            ->with($this->source, $this->document)
            ->andReturnTrue();

        $this->source
            ->expects('getFileInfo->getType')
            ->andReturn('pdf');

        $this->fileInfo
            ->expects('setType')
            ->with('pdf');

        $this->fileInfo
            ->expects('setPageCount')
            ->with(null);

        $this->doctrine->expects('persist')->with($this->document);
        $this->doctrine->expects('flush');

        $this->fileStorer->storeForDocument($this->source, $this->document, $documentId);
    }

    public function testStoreForAlreadyUploadedDocumentRemovedOldFilesFirst(): void
    {
        $documentId = DocumentId::create('foo.123');
        $this->document->expects('getFileInfo')
            ->times(2)
            ->andReturn($this->fileInfo);

        $this->fileInfo
            ->expects('isUploaded')
            ->andReturnTrue();

        $this->thumbnailStorageService->expects('deleteAllThumbsForEntity')->with($this->document);

        $this->entityStorageService
            ->expects('copyEntity')
            ->with($this->source, $this->document)
            ->andReturnTrue();

        $this->source
            ->expects('getFileInfo->getType')
            ->andReturn('pdf');

        $this->fileInfo
            ->expects('setType')
            ->with('pdf');

        $this->fileInfo
            ->expects('setPageCount')
            ->with(null);

        $this->doctrine->expects('persist')->with($this->document);
        $this->doctrine->expects('flush');

        $this->fileStorer->storeForDocument($this->source, $this->document, $documentId);
    }

    public function testStoreForDocumentWithFailure(): void
    {
        $documentId = DocumentId::create('foo.123');
        $this->document->expects('getFileInfo')
            ->andReturn($this->fileInfo);

        $this->fileInfo
            ->expects('isUploaded')
            ->andReturnFalse();

        $this->entityStorageService
            ->expects('copyEntity')
            ->with($this->source, $this->document)
            ->andReturnFalse();

        $sourceFileInfo = Mockery::mock(FileInfo::class);
        $sourceFileInfo->expects('getPath')
            ->times(3)
            ->andReturn($expectedPath = 'path');

        $this->source->expects('getFileInfo')
            ->times(3)
            ->andReturn($sourceFileInfo);

        $this->logger
            ->expects('error')
            ->with('Failed to store document', [
                'documentId' => $documentId,
                'path' => $expectedPath,
            ]);

        $this->expectExceptionObject(FileProcessException::forFailingToStoreDocument($this->source, $documentId));

        $this->fileStorer->storeForDocument($this->source, $this->document, $documentId);
    }
}
