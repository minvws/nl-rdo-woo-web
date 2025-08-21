<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Ingest\Content;

use App\Domain\Ingest\Content\ContentExtractOptions;
use App\Domain\Ingest\Content\Extractor\ContentExtractorInterface;
use App\Domain\Ingest\Content\Extractor\ContentExtractorKey;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class ContentExtractOptionsTest extends MockeryTestCase
{
    public function testCreateWithDefaultOptions(): void
    {
        $options = ContentExtractOptions::create();

        self::assertFalse($options->hasPageNumber());
        self::assertNull($options->getPageNumber());
        self::assertCount(0, $options->getEnabledExtractors());
    }

    public function testWithAllExtractors(): void
    {
        $options = ContentExtractOptions::create()->withAllExtractors();

        self::assertCount(count(ContentExtractorKey::cases()), $options->getEnabledExtractors());
    }

    public function testWithAllExtractor(): void
    {
        $options = ContentExtractOptions::create()->withExtractor(ContentExtractorKey::TIKA);

        self::assertEquals(
            [ContentExtractorKey::TIKA->value => ContentExtractorKey::TIKA],
            $options->getEnabledExtractors(),
        );
    }

    public function testIsExtractorEnabled(): void
    {
        $options = ContentExtractOptions::create()->withExtractor(ContentExtractorKey::TIKA);

        $extractor = \Mockery::mock(ContentExtractorInterface::class);
        $extractor->expects('getKey')->andReturn(ContentExtractorKey::TIKA);

        self::assertTrue(
            $options->isExtractorEnabled($extractor)
        );

        $extractor->expects('getKey')->andReturn(ContentExtractorKey::TESSERACT);

        self::assertFalse(
            $options->isExtractorEnabled($extractor)
        );
    }

    public function testWithPageNumber(): void
    {
        $options = ContentExtractOptions::create()->withPageNumber(13);

        self::assertTrue($options->hasPageNumber());
        self::assertEquals(
            13,
            $options->getPageNumber(),
        );
    }

    public function testWithLocalFile(): void
    {
        $options = ContentExtractOptions::create()->withLocalFile($localFile = '/foo/bar.txt');

        self::assertTrue($options->hasLocalFile());
        self::assertEquals(
            $localFile,
            $options->getLocalFile(),
        );
    }
}
