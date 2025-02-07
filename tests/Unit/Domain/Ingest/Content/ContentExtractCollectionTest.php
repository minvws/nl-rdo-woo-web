<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Ingest\Content;

use App\Domain\Ingest\Content\ContentExtract;
use App\Domain\Ingest\Content\ContentExtractCollection;
use App\Domain\Ingest\Content\Extractor\ContentExtractorKey;
use PHPUnit\Framework\TestCase;

class ContentExtractCollectionTest extends TestCase
{
    public function testAppendSuccessful(): void
    {
        $extracts = new ContentExtractCollection();

        $extractA = new ContentExtract(
            ContentExtractorKey::TESSERACT,
            $contentA = 'foo',
        );
        $extracts->append($extractA);

        $extractB = new ContentExtract(
            ContentExtractorKey::TIKA,
            $contentB = 'bar',
        );
        $extracts->append($extractB);

        self::assertFalse($extracts->isFailure());
        self::assertFalse($extracts->isEmpty());
        self::assertEquals($contentA . PHP_EOL . $contentB, $extracts->getCombinedContent());
    }

    public function testFailure(): void
    {
        $extracts = new ContentExtractCollection();
        $extracts->markAsFailure();

        self::assertTrue($extracts->isFailure());
        self::assertTrue($extracts->isEmpty());
        self::assertEquals('', $extracts->getCombinedContent());
    }

    public function testIterator(): void
    {
        $extracts = new ContentExtractCollection();

        $extractA = new ContentExtract(
            ContentExtractorKey::TESSERACT,
            'foo',
        );
        $extracts->append($extractA);

        $extractB = new ContentExtract(
            ContentExtractorKey::TIKA,
            'bar',
        );
        $extracts->append($extractB);

        self::assertEquals(
            [$extractA, $extractB],
            iterator_to_array($extracts)
        );
    }
}
