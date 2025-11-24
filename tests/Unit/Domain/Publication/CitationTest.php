<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication;

use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Domain\Publication\Citation;
use Shared\Tests\Unit\UnitTestCase;

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

    #[DataProvider('getCitationTypeData')]
    public function testGetCitationType(string $input, string $expected): void
    {
        $this->assertSame($expected, Citation::getCitationType($input));
    }

    /**
     * @return array<string,array{input:string,expected:string}>
     */
    public static function getCitationTypeData(): array
    {
        return [
            'wob' => [
                'input' => '10.1c',
                'expected' => Citation::TYPE_WOB,
            ],
            'wob-with-whitespace-and-casing' => [
                'input' => ' 10.1 C ',
                'expected' => Citation::TYPE_WOB,
            ],
            'woo' => [
                'input' => '5.1.2i',
                'expected' => Citation::TYPE_WOO,
            ],
            'woo-with-whitespace-and-casing' => [
                'input' => ' 5.1.2  I ',
                'expected' => Citation::TYPE_WOO,
            ],
            'non-existing' => [
                'input' => 'foo',
                'expected' => Citation::TYPE_UNKNOWN,
            ],
        ];
    }

    #[DataProvider('toClassificationData')]
    public function testToClassification(string $input, string $expected): void
    {
        self::assertEquals($expected, Citation::toClassification($input));
    }

    /**
     * @return array<string,array{input:string,expected:string}>
     */
    public static function toClassificationData(): array
    {
        return [
            'wob' => [
                'input' => '10.1c',
                'expected' => 'Vertrouwelijk verstrekte bedrijfs- en fabricagegegevens',
            ],
            'woo' => [
                'input' => '5.1.2i',
                'expected' => 'Het goed functioneren van de staat, andere publiekrechtelijke lichamen of bestuursorganen',
            ],
            'non-existing' => [
                'input' => 'foo',
                'expected' => '',
            ],
        ];
    }
}
