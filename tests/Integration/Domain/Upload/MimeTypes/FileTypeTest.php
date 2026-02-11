<?php

declare(strict_types=1);

namespace Integration\Domain\Upload\MimeTypes;

use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Domain\Upload\FileType\FileType;
use Shared\Tests\Integration\SharedWebTestCase;

class FileTypeTest extends SharedWebTestCase
{
    #[DataProvider('fromMimeTypeDataProvider')]
    public function testFromMimeType(string $mimeType, ?FileType $expectedFileType): void
    {
        self::assertEquals($expectedFileType, FileType::fromMimeType($mimeType));
    }

    /**
     * @return array<string,array<string|FileType|null>>
     */
    public static function fromMimeTypeDataProvider(): array
    {
        return [
            'empty string' => ['', null],
            'unknown mimeType' => ['unknown', null],
            'pdf' => ['application/pdf', FileType::PDF],
            'ndjason' => ['application/x-ndjason', FileType::TXT],
        ];
    }
}
