<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Entity;

use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\DocumentFileSet;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\DocumentFileUpdate;
use App\Domain\Publication\Dossier\Type\WooDecision\Enum\DocumentFileUpdateStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\Enum\DocumentFileUpdateType;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class DocumentFileUpdateTest extends MockeryTestCase
{
    public function testGetters(): void
    {
        $documentFileSet = \Mockery::mock(DocumentFileSet::class);
        $document = \Mockery::mock(Document::class);
        $documentFileUpdate = new DocumentFileUpdate($documentFileSet, $document, DocumentFileUpdateType::ADD);

        self::assertEquals(DocumentFileUpdateStatus::PENDING, $documentFileUpdate->getStatus());
        self::assertEquals(DocumentFileUpdateType::ADD, $documentFileUpdate->getType());
        self::assertEquals($documentFileSet, $documentFileUpdate->getDocumentFileSet());
        self::assertEquals($document, $documentFileUpdate->getDocument());
    }

    public function testSetAndGetStatus(): void
    {
        $documentFileSet = \Mockery::mock(DocumentFileSet::class);
        $document = \Mockery::mock(Document::class);
        $documentFileUpdate = new DocumentFileUpdate($documentFileSet, $document, DocumentFileUpdateType::ADD);
        $documentFileUpdate->setStatus(DocumentFileUpdateStatus::COMPLETED);

        self::assertEquals(DocumentFileUpdateStatus::COMPLETED, $documentFileUpdate->getStatus());
    }
}
