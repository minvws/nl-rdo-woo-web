<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum;

use Mockery;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum\DocumentFileUpdateType;
use Shared\Tests\Unit\UnitTestCase;

class DocumentFileUpdateTypeTest extends UnitTestCase
{
    public function testForDocumentWithWithdrawnDocument(): void
    {
        $document = Mockery::mock(Document::class);
        $document->shouldReceive('isWithdrawn')->andReturnTrue();

        $updateType = DocumentFileUpdateType::forDocument($document);

        self::assertEquals(
            DocumentFileUpdateType::REPUBLISH,
            $updateType,
        );
    }

    public function testForDocumentWithUploadedDocument(): void
    {
        $document = Mockery::mock(Document::class);
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
        $document = Mockery::mock(Document::class);
        $document->shouldReceive('isWithdrawn')->andReturnFalse();
        $document->shouldReceive('isUploaded')->andReturnFalse();

        $updateType = DocumentFileUpdateType::forDocument($document);

        self::assertEquals(
            DocumentFileUpdateType::ADD,
            $updateType,
        );
    }
}
