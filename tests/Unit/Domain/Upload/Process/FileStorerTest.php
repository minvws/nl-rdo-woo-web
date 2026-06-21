<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload\Process;

use Doctrine\ORM\EntityManagerInterface;
use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\FileInfo;
use Shared\Domain\Upload\Process\FileProcessException;
use Shared\Domain\Upload\Process\FileStorer;
use Shared\Domain\Upload\UploadedFile;
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
    private UploadedFile&MockInterface $file;
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
        $this->file = Mockery::mock(UploadedFile::class);
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
            ->expects('storeEntity')
            ->with($this->file, $this->document)
            ->andReturnTrue();

        $this->file
            ->expects('getOriginalFileExtension')
            ->andReturn('pdf');

        $this->fileInfo
            ->expects('setType')
            ->with('pdf');

        $this->fileInfo
            ->expects('setPageCount')
            ->with(null);

        $this->doctrine->expects('persist')->with($this->document);
        $this->doctrine->expects('flush');

        $this->fileStorer->storeForDocument($this->file, $this->document, $documentId);
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
            ->expects('storeEntity')
            ->with($this->file, $this->document)
            ->andReturnTrue();

        $this->file
            ->expects('getOriginalFileExtension')
            ->andReturn('pdf');

        $this->fileInfo
            ->expects('setType')
            ->with('pdf');

        $this->fileInfo
            ->expects('setPageCount')
            ->with(null);

        $this->doctrine->expects('persist')->with($this->document);
        $this->doctrine->expects('flush');

        $this->fileStorer->storeForDocument($this->file, $this->document, $documentId);
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
            ->expects('storeEntity')
            ->with($this->file, $this->document)
            ->andReturnFalse();

        $this->file->expects('getPathname')
            ->times(3)
            ->andReturn($expectedPath = 'path');

        $this->logger
            ->expects('error')
            ->with('Failed to store document', [
                'documentId' => $documentId,
                'path' => $expectedPath,
            ]);

        $this->expectExceptionObject(FileProcessException::forFailingToStoreDocument($this->file, $documentId));

        $this->fileStorer->storeForDocument($this->file, $this->document, $documentId);
    }
}
