<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileSet;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileUpdate;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum\DocumentFileUpdateStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum\DocumentFileUpdateType;
use Shared\Domain\Publication\FileInfo;
use Shared\Tests\Unit\UnitTestCase;

class DocumentFileUpdateTest extends UnitTestCase
{
    public function testGetters(): void
    {
        $documentFileSet = \Mockery::mock(DocumentFileSet::class);

        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('isWithdrawn')->andReturnFalse();
        $document->shouldReceive('isUploaded')->andReturnFalse();

        $documentFileUpdate = new DocumentFileUpdate($documentFileSet, $document);

        self::assertEquals(DocumentFileUpdateStatus::PENDING, $documentFileUpdate->getStatus());
        self::assertEquals(DocumentFileUpdateType::ADD, $documentFileUpdate->getType());
        self::assertEquals($documentFileSet, $documentFileUpdate->getDocumentFileSet());
        self::assertEquals($document, $documentFileUpdate->getDocument());
    }

    public function testSetAndGetStatus(): void
    {
        $documentFileSet = \Mockery::mock(DocumentFileSet::class);

        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('isWithdrawn')->andReturnFalse();
        $document->shouldReceive('isUploaded')->andReturnFalse();

        $documentFileUpdate = new DocumentFileUpdate($documentFileSet, $document);
        $documentFileUpdate->setStatus(DocumentFileUpdateStatus::COMPLETED);

        self::assertEquals(DocumentFileUpdateStatus::COMPLETED, $documentFileUpdate->getStatus());
    }

    public function testSetAndGetFileInfo(): void
    {
        $documentFileSet = \Mockery::mock(DocumentFileSet::class);

        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('isWithdrawn')->andReturnFalse();
        $document->shouldReceive('isUploaded')->andReturnFalse();

        $documentFileUpdate = new DocumentFileUpdate($documentFileSet, $document);

        $fileInfo = new FileInfo();
        $documentFileUpdate->setFileInfo($fileInfo);

        self::assertSame($fileInfo, $documentFileUpdate->getFileInfo());
    }
}
