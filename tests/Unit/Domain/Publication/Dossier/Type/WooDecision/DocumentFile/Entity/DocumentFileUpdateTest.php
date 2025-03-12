<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileSet;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileUpdate;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum\DocumentFileUpdateStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum\DocumentFileUpdateType;
use App\Domain\Publication\FileInfo;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class DocumentFileUpdateTest extends MockeryTestCase
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
