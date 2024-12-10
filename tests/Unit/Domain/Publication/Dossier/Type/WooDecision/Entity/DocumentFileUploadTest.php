<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Entity;

use App\Domain\Publication\Dossier\Type\WooDecision\Entity\DocumentFileSet;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\DocumentFileUpload;
use App\Domain\Publication\Dossier\Type\WooDecision\Enum\DocumentFileUploadError;
use App\Domain\Publication\Dossier\Type\WooDecision\Enum\DocumentFileUploadStatus;
use App\Domain\Publication\FileInfo;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class DocumentFileUploadTest extends MockeryTestCase
{
    public function testGetters(): void
    {
        $documentFileSet = \Mockery::mock(DocumentFileSet::class);
        $documentFileUpload = new DocumentFileUpload($documentFileSet);

        self::assertEquals(DocumentFileUploadStatus::PENDING, $documentFileUpload->getStatus());
        self::assertEquals($documentFileSet, $documentFileUpload->getDocumentFileSet());
    }

    public function testSetAndGetStatus(): void
    {
        $documentFileSet = \Mockery::mock(DocumentFileSet::class);
        $documentFileUpload = new DocumentFileUpload($documentFileSet);
        $documentFileUpload->setStatus(DocumentFileUploadStatus::SUCCESSFUL);

        self::assertEquals(DocumentFileUploadStatus::SUCCESSFUL, $documentFileUpload->getStatus());
    }

    public function testSetAndGetError(): void
    {
        $documentFileSet = \Mockery::mock(DocumentFileSet::class);
        $documentFileUpload = new DocumentFileUpload($documentFileSet);
        $documentFileUpload->setError(DocumentFileUploadError::MAX_SIZE_EXCEEDED);

        self::assertEquals(DocumentFileUploadError::MAX_SIZE_EXCEEDED, $documentFileUpload->getError());
    }

    public function testSetAndGetFileInfo(): void
    {
        $documentFileSet = \Mockery::mock(DocumentFileSet::class);
        $documentFileUpload = new DocumentFileUpload($documentFileSet);

        $fileInfo = new FileInfo();
        $documentFileUpload->setFileInfo($fileInfo);

        self::assertSame($fileInfo, $documentFileUpload->getFileInfo());
    }
}
