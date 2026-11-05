<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication;

use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Domain\Publication\SourceType;
use Shared\Tests\Unit\UnitTestCase;

class SourceTypeTest extends UnitTestCase
{
    #[DataProvider('createProvider')]
    public function testCreate(?string $input, SourceType $expectedResult): void
    {
        self::assertEquals($expectedResult, SourceType::create($input));
    }

    public function testFromMimeTypeMapsPlainTextToTxt(): void
    {
        self::assertSame(SourceType::TXT, SourceType::fromMimeType('text/plain'));
    }

    public function testFromMimeTypeMapsWordDocumentsToDoc(): void
    {
        self::assertSame(SourceType::DOC, SourceType::fromMimeType('application/msword'));
    }

    public function testFromMimeTypeMapsRtfToTxt(): void
    {
        self::assertSame(SourceType::TXT, SourceType::fromMimeType('application/rtf'));
    }

    public function testFromMimeTypeReturnsUnknownForMissingMimeType(): void
    {
        self::assertSame(SourceType::UNKNOWN, SourceType::fromMimeType(null));
    }

    public function testFromMimeTypeReturnsUnknownForUnsupportedMimeType(): void
    {
        self::assertSame(SourceType::UNKNOWN, SourceType::fromMimeType('application/unknown'));
    }

    public function testFromMimeTypeMapsZipToUnknown(): void
    {
        self::assertSame(SourceType::UNKNOWN, SourceType::fromMimeType('application/zip'));
    }

    /**
     * @return array<string, array{input:?string, expectedResult:SourceType}>
     */
    public static function createProvider(): array
    {
        return [
            'empty-string' => [
                'input' => '',
                'expectedResult' => SourceType::UNKNOWN,
            ],
            'null' => [
                'input' => null,
                'expectedResult' => SourceType::UNKNOWN,
            ],
            'PDF-whitespaced-and-uppercased' => [
                'input' => ' PDF   ',
                'expectedResult' => SourceType::PDF,
            ],
            'mimetype' => [
                'input' => 'application/vnd.openxmlformats-officedocument',
                'expectedResult' => SourceType::DOC,
            ],
            'image' => [
                'input' => 'image',
                'expectedResult' => SourceType::IMAGE,
            ],
            'presentation' => [
                'input' => 'presentation',
                'expectedResult' => SourceType::PRESENTATION,
            ],
            'spreadsheet' => [
                'input' => 'spreadsheet',
                'expectedResult' => SourceType::SPREADSHEET,
            ],
            'html' => [
                'input' => 'html',
                'expectedResult' => SourceType::HTML,
            ],
            'note' => [
                'input' => 'application/msonenote',
                'expectedResult' => SourceType::NOTE,
            ],
            'database' => [
                'input' => 'application/x-sqlite3',
                'expectedResult' => SourceType::DATABASE,
            ],
            'xml' => [
                'input' => 'xml',
                'expectedResult' => SourceType::XML,
            ],
            'video' => [
                'input' => 'video',
                'expectedResult' => SourceType::VIDEO,
            ],
            'vcard' => [
                'input' => 'vcard',
                'expectedResult' => SourceType::VCARD,
            ],
            'chat' => [
                'input' => 'chat',
                'expectedResult' => SourceType::CHAT,
            ],
            'chatbericht' => [
                'input' => 'Chatbericht ',
                'expectedResult' => SourceType::CHAT,
            ],
        ];
    }

    public function testCreateRecognizesTextSourceType(): void
    {
        self::assertSame(SourceType::TXT, SourceType::create('txt'));
    }

    public function testIsEmail(): void
    {
        self::assertFalse(SourceType::DOC->isEmail());
        self::assertTrue(SourceType::EMAIL->isEmail());
    }
}
