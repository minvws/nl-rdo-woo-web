<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Inventory;

use App\Service\Inventory\InventoryDataHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class InventoryDataHelperTest extends TestCase
{
    /**
     * @param string[] $expectedResult
     */
    #[DataProvider('separateValuesProvider')]
    public function testSeparateValues(string $input, string $separator, array $expectedResult): void
    {
        $this->assertEquals($expectedResult, array_values(InventoryDataHelper::separateValues($input, $separator)));
    }

    /**
     * @return array<string, array{input:string, separator:string, expectedResult:string[]}
     */
    public static function separateValuesProvider(): array
    {
        return [
            'semicolon-separated' => [
                'input' => 'test;123',
                'separator' => ';',
                'expectedResult' => ['test', '123'],
            ],
            'semicolon-separated-with-whitespace' => [
                'input' => ' test ;   123;x   ',
                'separator' => ';',
                'expectedResult' => ['test', '123', 'x'],
            ],
            'pipe-separated' => [
                'input' => ' test|123; x   ',
                'separator' => '|',
                'expectedResult' => ['test', '123; x'],
            ],
            'empty-values-and-adjacent-separators-are-ignored' => [
                'input' => ' test|| | 123',
                'separator' => '|',
                'expectedResult' => ['test', '123'],
            ],
        ];
    }

    #[DataProvider('toDateTimeImmutableProvider')]
    public function testToDateTimeImmutable(string $input, ?\DateTimeImmutable $expectedResult): void
    {
        if ($expectedResult === null) {
            $this->expectException(\RuntimeException::class);
        }

        $this->assertEquals($expectedResult, InventoryDataHelper::toDateTimeImmutable($input));
    }

    /**
     * @return array<string, array{input:string, expectedResult:\DateTimeImmutable[]}
     */
    public static function toDateTimeImmutableProvider(): array
    {
        $sixthOfMay2021 = \DateTimeImmutable::createFromFormat('!Y-m-d', '2021-05-06');

        return [
            'empty-input-throws-exception' => [
                'input' => '',
                'expectedResult' => null,
            ],
            'old-inventory-format' => [
                'input' => '10/9/2020 1:34 PM UTC',
                'expectedResult' => new \DateTimeImmutable('2020-10-09 13:34'),
            ],
            'YYYY-MM-DD' => [
                'input' => '2021-05-06',
                'expectedResult' => $sixthOfMay2021,
            ],
            'DD-MM-YYYY' => [
                'input' => '06-05-2021',
                'expectedResult' => $sixthOfMay2021,
            ],
            'YYYY/MM/DD' => [
                'input' => '2021/05/06',
                'expectedResult' => $sixthOfMay2021,
            ],
            'DD/MM/YYYY' => [
                'input' => '06/05/2021',
                'expectedResult' => $sixthOfMay2021,
            ],
            'DD/MM/YYYY h:m' => [
                'input' => '06/05/2021 08:11',
                'expectedResult' => \DateTimeImmutable::createFromFormat('Y-m-d H:i', '2021-05-06 08:11'),
            ],
            'invalid-input-throws-exception' => [
                'input' => 'test-1231 foo bar',
                'expectedResult' => null,
            ],
            'unsupported-format-throws-exception' => [
                'input' => '06/05/2021 23:11 UTC',
                'expectedResult' => null,
            ],
        ];
    }

    /**
     * @param array<array-key, array{input:string, expectedResult:string[]}> $expectedResult
     */
    #[DataProvider('getGroundsProvider')]
    public function testGetGrounds(string $input, array $expectedResult): void
    {
        $this->assertEquals($expectedResult, InventoryDataHelper::getGrounds($input));
    }

    /**
     * @return array<array-key, array{input:string, expectedResult:string[]}>
     */
    public static function getGroundsProvider(): array
    {
        return [
            'single-value-without-normalization-is-returned-as-is' => [
                'input' => 'foo',
                'expectedResult' => ['foo'],
            ],
            'single-value-with-normalization' => [
                'input' => '5.1.2.i',
                'expectedResult' => ['5.1.2i'],
            ],
            'two-values-with-and-without-normalization' => [
                'input' => 'foo;5.1.2.i',
                'expectedResult' => ['foo', '5.1.2i'],
            ],
            'two-values-with-and-without-normalization-including-whitespace' => [
                'input' => '  foo ;   5.1.2.i  ',
                'expectedResult' => ['foo', '5.1.2i'],
            ],
            'empty-string' => [
                'input' => '',
                'expectedResult' => [],
            ],
        ];
    }
}
