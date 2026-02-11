<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity;

use Mockery;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileSet;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileUpload;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum\DocumentFileUploadError;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum\DocumentFileUploadStatus;
use Shared\Domain\Publication\FileInfo;
use Shared\Tests\Unit\UnitTestCase;

class DocumentFileUploadTest extends UnitTestCase
{
    public function testGetters(): void
    {
        $documentFileSet = Mockery::mock(DocumentFileSet::class);
        $documentFileUpload = new DocumentFileUpload($documentFileSet);

        self::assertEquals(DocumentFileUploadStatus::PENDING, $documentFileUpload->getStatus());
        self::assertEquals($documentFileSet, $documentFileUpload->getDocumentFileSet());
    }

    public function testSetAndGetStatus(): void
    {
        $documentFileSet = Mockery::mock(DocumentFileSet::class);
        $documentFileUpload = new DocumentFileUpload($documentFileSet);
        $documentFileUpload->setStatus(DocumentFileUploadStatus::UPLOADED);

        self::assertEquals(DocumentFileUploadStatus::UPLOADED, $documentFileUpload->getStatus());
    }

    public function testSetAndGetError(): void
    {
        $documentFileSet = Mockery::mock(DocumentFileSet::class);
        $documentFileUpload = new DocumentFileUpload($documentFileSet);
        $documentFileUpload->setError(DocumentFileUploadError::MAX_SIZE_EXCEEDED);

        self::assertEquals(DocumentFileUploadError::MAX_SIZE_EXCEEDED, $documentFileUpload->getError());
    }

    public function testSetAndGetFileInfo(): void
    {
        $documentFileSet = Mockery::mock(DocumentFileSet::class);
        $documentFileUpload = new DocumentFileUpload($documentFileSet);

        $fileInfo = new FileInfo();
        $documentFileUpload->setFileInfo($fileInfo);

        self::assertSame($fileInfo, $documentFileUpload->getFileInfo());
        self::assertSame($documentFileUpload->getId()->toRfc4122(), $documentFileUpload->getFileCacheKey());
    }

    public function testMarkAsProcessed(): void
    {
        $documentFileSet = Mockery::mock(DocumentFileSet::class);
        $documentFileUpload = new DocumentFileUpload($documentFileSet);

        self::assertFalse($documentFileUpload->getStatus()->isProcessed());

        $documentFileUpload->markAsProcessed();

        self::assertTrue($documentFileUpload->getStatus()->isProcessed());
    }
}
