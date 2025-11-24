<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shared\Domain\Publication\SourceType;
use Shared\Domain\Upload\FileType\FileType;

class SourceTypeTest extends TestCase
{
    #[DataProvider('createProvider')]
    public function testCreate(?string $input, SourceType $expectedResult): void
    {
        self::assertEquals($expectedResult, SourceType::create($input));
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

    #[DataProvider('fromFileTypeProvider')]
    public function testFromFileType(FileType $fileType, SourceType $expectedResult): void
    {
        self::assertEquals($expectedResult, SourceType::fromFileType($fileType));
    }

    /**
     * @return array<string, array{fileType:FileType, expectedResult:SourceType}>
     */
    public static function fromFileTypeProvider(): array
    {
        return [
            'doc' => [
                'fileType' => FileType::DOC,
                'expectedResult' => SourceType::DOC,
            ],
            'zip' => [
                'fileType' => FileType::ZIP,
                'expectedResult' => SourceType::UNKNOWN,
            ],
        ];
    }

    public function testIsEmail(): void
    {
        self::assertFalse(SourceType::DOC->isEmail());
        self::assertTrue(SourceType::EMAIL->isEmail());
    }
}
