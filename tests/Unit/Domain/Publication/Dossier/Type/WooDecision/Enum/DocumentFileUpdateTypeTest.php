<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Enum;

use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Enum\DocumentFileUpdateType;
use App\Tests\Unit\UnitTestCase;

class DocumentFileUpdateTypeTest extends UnitTestCase
{
    public function testForDocumentWithWithdrawnDocument(): void
    {
        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('isWithdrawn')->andReturnTrue();

        $updateType = DocumentFileUpdateType::forDocument($document);

        self::assertEquals(
            DocumentFileUpdateType::REPUBLISH,
            $updateType,
        );
    }

    public function testForDocumentWithUploadedDocument(): void
    {
        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('isWithdrawn')->andReturnFalse();
        $document->shouldReceive('isUploaded')->andReturnTrue();

        $updateType = DocumentFileUpdateType::forDocument($document);

        self::assertEquals(
            DocumentFileUpdateType::UPDATE,
            $updateType,
        );
    }

    public function testForDocumentWithoutUploadedDocument(): void
    {
        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('isWithdrawn')->andReturnFalse();
        $document->shouldReceive('isUploaded')->andReturnFalse();

        $updateType = DocumentFileUpdateType::forDocument($document);

        self::assertEquals(
            DocumentFileUpdateType::ADD,
            $updateType,
        );
    }
}
