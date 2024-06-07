<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Citation;
use PHPUnit\Framework\Attributes\DataProvider;

final class CitationTest extends UnitTestCase
{
    /**
     * @param array<array-key,string> $input
     * @param list<string>            $expected
     */
    #[DataProvider('getSortWooCitationsData')]
    public function testSortWooCitations(array $input, array $expected): void
    {
        $this->assertSame($expected, Citation::sortWooCitations($input));
    }

    /**
     * @return array<string,array{input:array<array-key,string>,expected:list<string>}>
     */
    public static function getSortWooCitationsData(): array
    {
        return [
            'only using known citations' => [
                'input' => [
                    '5.2',
                    '5.1.2b',
                    '5.1.1b',
                    '5.1.1c',
                ],
                'expected' => [
                    '5.1.1b',
                    '5.1.1c',
                    '5.1.2b',
                    '5.2',
                ],
            ],
            'only using unknown citations' => [
                'input' => [
                    'BBB',
                    '111',
                    'AAA',
                ],
                'expected' => [
                    '111',
                    'AAA',
                    'BBB',
                ],
            ],
            'using a mix of known and unknown citations' => [
                'input' => [
                    'BBB',
                    '5.2',
                    '5.1.2b',
                    '111',
                    'AAA',
                    '5.1.1b',
                    '5.1.1c',
                ],
                'expected' => [
                    '5.1.1b',
                    '5.1.1c',
                    '5.1.2b',
                    '5.2',
                    '111',
                    'AAA',
                    'BBB',
                ],
            ],
            'ignores keys of input' => [
                'input' => [
                    1 => '5.2',
                    'two' => '5.1.2b',
                    'three' => '5.1.1b',
                    2 => '5.1.1c',
                ],
                'expected' => [
                    '5.1.1b',
                    '5.1.1c',
                    '5.1.2b',
                    '5.2',
                ],
            ],
        ];
    }
}
