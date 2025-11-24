<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Ingest\Content;

use PHPUnit\Framework\TestCase;
use Shared\Domain\Ingest\Content\ContentExtract;
use Shared\Domain\Ingest\Content\Extractor\ContentExtractorKey;

class ContentExtractTest extends TestCase
{
    public function testGetters(): void
    {
        $extract = new ContentExtract(
            $key = ContentExtractorKey::TESSERACT,
            $content = 'foo bar',
        );

        self::assertEquals($key, $extract->key);
        self::assertEquals($content, $extract->content);

        $now = new \DateTimeImmutable();
        self::assertLessThanOrEqual(1, abs($now->diff($extract->date)->s));
    }
}
