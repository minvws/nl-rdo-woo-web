<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Publication\Dossier\WooDecision\Uploads\Document;

use InvalidArgumentException;
use Mockery;
use PublicationApi\Api\Publication\Dossier\WooDecision\Uploads\Document\DocumentFileName;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Tests\Unit\UnitTestCase;

final class DocumentFileNameTest extends UnitTestCase
{
    public function testDocumentFileName(): void
    {
        $document = Mockery::mock(Document::class);
        $document->expects('getFileInfo->getName')->andReturn('my-fancy-name.pdf');
        $document->expects('getDocumentId')->andReturn('1337');

        $documentFileName = new DocumentFileName($document);

        $this->assertMatchesObjectSnapshot($documentFileName);
    }

    public function testThrowsExceptionIfFileInfoHasNoName(): void
    {
        $document = Mockery::mock(Document::class);
        $document->expects('getFileInfo->getName')->andReturnNull();

        $this->expectExceptionObject(new InvalidArgumentException('Document file info name must be a string'));

        new DocumentFileName($document);
    }

    public function testThrowsExceptionIfFileNameHasNoExtension(): void
    {
        $document = Mockery::mock(Document::class);
        $document->expects('getFileInfo->getName')->andReturn('my-fancy-name');

        $this->expectExceptionObject(new InvalidArgumentException('Document file name must have an extension'));

        new DocumentFileName($document);
    }

    public function testThrowsExceptionIfDocumentHasNoDocumentId(): void
    {
        $document = Mockery::mock(Document::class);
        $document->expects('getFileInfo->getName')->andReturn('my-fancy-name.pdf');
        $document->expects('getDocumentId')->andReturnNull();

        $this->expectExceptionObject(new InvalidArgumentException('Document must have a documentId'));

        new DocumentFileName($document);
    }
}
