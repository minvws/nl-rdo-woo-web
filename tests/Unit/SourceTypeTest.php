<?php

declare(strict_types=1);

namespace App\Tests\Unit;

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
}
