<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Result\FacetValue;

use App\Domain\Search\Result\FacetValue\AbbreviatedValue;
use App\Entity\Department;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class AbbreviatedValueTest extends MockeryTestCase
{
    public function testFromDepartment(): void
    {
        $department = \Mockery::mock(Department::class);
        $department->expects('getName')->andReturn('Foo');
        $department->expects('getShortTag')->andReturn('F');

        $value = AbbreviatedValue::fromDepartment($department);

        self::assertEquals('F', $value->getValue());
        self::assertEquals('Foo', $value->getDescription());
        self::assertEquals('F|Foo', $value->getIndexValue());
    }

    #[DataProvider('fromStringProvider')]
    public function testFromString(
        string $rawValue,
        ?string $expectedValue,
        string $expectedDescription,
        string $expectedFallbackValue,
    ): void {
        $value = AbbreviatedValue::fromString($rawValue);

        self::assertEquals($expectedValue, $value->getValue());
        self::assertEquals($expectedDescription, $value->getDescription());
        self::assertEquals($rawValue, $value->getIndexValue());
        self::assertEquals($expectedFallbackValue, $value->__toString());
        self::assertEquals($expectedFallbackValue, strval($value));
    }

    /**
     * @return array<array-key,array{
     *     rawValue: string,
     *     expectedValue: ?string,
     *     expectedDescription: string,
     *     expectedFallbackValue: string
     * }>
     */
    public static function fromStringProvider(): array
    {
        return [
            'simple-string-without-abbreviation' => [
                'rawValue' => 'abc',
                'expectedValue' => null,
                'expectedDescription' => 'abc',
                'expectedFallbackValue' => 'abc',
            ],
            'simple-string-with-abbreviation' => [
                'rawValue' => 'abc|def',
                'expectedValue' => 'abc',
                'expectedDescription' => 'def',
                'expectedFallbackValue' => 'abc',
            ],
            'empty-string' => [
                'rawValue' => '',
                'expectedValue' => null,
                'expectedDescription' => '',
                'expectedFallbackValue' => '',
            ],
            'multiple-separators' => [
                'rawValue' => 'abc|def|ghi',
                'expectedValue' => 'abc',
                'expectedDescription' => 'def|ghi',
                'expectedFallbackValue' => 'abc',
            ],
        ];
    }
}
