<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Upload\FileType\FileType;
use App\SourceType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SourceTypeTest extends TestCase
{
    #[DataProvider('getTypeProvider')]
    public function testGetType(?string $input, string $expectedResult): void
    {
        self::assertEquals($expectedResult, SourceType::getType($input));
    }

    /**
     * @return array<string, array{input:?string, expectedResult:string}>
     */
    public static function getTypeProvider(): array
    {
        return [
            'empty-string' => [
                'input' => '',
                'expectedResult' => SourceType::SOURCE_UNKNOWN,
            ],
            'null' => [
                'input' => null,
                'expectedResult' => SourceType::SOURCE_UNKNOWN,
            ],
            'PDF-whitespaced-and-uppercased' => [
                'input' => ' PDF   ',
                'expectedResult' => SourceType::SOURCE_PDF,
            ],
            'mimetype' => [
                'input' => 'application/vnd.openxmlformats-officedocument',
                'expectedResult' => SourceType::SOURCE_DOCUMENT,
            ],
        ];
    }

    #[DataProvider('fromFileTypeProvider')]
    public function testFromFileType(FileType $fileType, string $expectedResult): void
    {
        self::assertEquals($expectedResult, SourceType::fromFileType($fileType));
    }

    /**
     * @return array<string, array{fileType:FileType, expectedResult:string}>
     */
    public static function fromFileTypeProvider(): array
    {
        return [
            'doc' => [
                'fileType' => FileType::DOC,
                'expectedResult' => SourceType::SOURCE_DOCUMENT,
            ],
            'zip' => [
                'fileType' => FileType::ZIP,
                'expectedResult' => SourceType::SOURCE_UNKNOWN,
            ],
        ];
    }

    #[DataProvider('getIconProvider')]
    public function testGetIcon(string $input, string $expectedResult): void
    {
        self::assertEquals($expectedResult, SourceType::getIcon($input));
    }

    /**
     * @return array<string, array{input:?string, expectedResult:string}>
     */
    public static function getIconProvider(): array
    {
        return [
            'empty-string' => [
                'input' => '',
                'expectedResult' => 'fas fa-file',
            ],
            'pdf' => [
                'input' => SourceType::SOURCE_PDF,
                'expectedResult' => 'fas fa-file-pdf',
            ],
            'foo' => [
                'input' => 'foo',
                'expectedResult' => 'fas fa-file',
            ],
            'email' => [
                'input' => SourceType::SOURCE_EMAIL,
                'expectedResult' => 'fas fa-envelope',
            ],
        ];
    }
}
