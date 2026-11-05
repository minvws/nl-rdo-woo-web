<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Ingest\Content;

use Shared\Domain\Ingest\Content\ContentExtractException;
use Shared\Domain\Ingest\Content\ContentExtractOptions;
use Shared\Domain\Ingest\Content\FileReference;
use Shared\Tests\Unit\UnitTestCase;

use function stream_get_contents;

final class FileReferenceTest extends UnitTestCase
{
    public function testGetStream(): void
    {
        $fileContents = $this->getFaker()->sentence();

        $fileReference = FileReference::forContentExtractOptions(
            ContentExtractOptions::create()->withFileContents($fileContents),
        );

        self::assertSame($fileContents, stream_get_contents($fileReference->getStream()));
    }

    public function testForContentExtractOptionsThrowsWithoutFileContents(): void
    {
        $this->expectExceptionObject(ContentExtractException::forNoFileContentsInContentExtractOptions());

        FileReference::forContentExtractOptions(ContentExtractOptions::create());
    }
}
