<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Index\Dossier\Mapper;

use App\Domain\Search\Index\Dossier\Mapper\DepartmentFieldMapper;
use App\Entity\Department;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class DepartmentFieldMapperTest extends MockeryTestCase
{
    public function testFromDepartment(): void
    {
        $department = \Mockery::mock(Department::class);
        $department->expects('getName')->andReturn('Foo');
        $department->expects('getShortTag')->andReturn('F');

        $value = DepartmentFieldMapper::fromDepartment($department);

        self::assertEquals('F', $value->getValue());
        self::assertEquals('Foo', $value->getDescription());
        self::assertEquals('F|Foo', $value->getIndexValue());
    }

    #[DataProvider('fromStringProvider')]
    public function testFromString(
        string $rawValue,
        ?string $expectedValue,
        string $expectedDescription,
    ): void {
        $value = DepartmentFieldMapper::fromString($rawValue);

        self::assertEquals($expectedValue, $value->getValue());
        self::assertEquals($expectedDescription, $value->getDescription());
        self::assertEquals($rawValue, $value->getIndexValue());
    }

    /**
     * @return array<array-key,array{
     *     rawValue: string,
     *     expectedValue: ?string,
     *     expectedDescription: string,
     * }>
     */
    public static function fromStringProvider(): array
    {
        return [
            'simple-string-without-abbreviation' => [
                'rawValue' => 'abc',
                'expectedValue' => 'abc',
                'expectedDescription' => 'abc',
            ],
            'simple-string-with-abbreviation' => [
                'rawValue' => 'abc|def',
                'expectedValue' => 'abc',
                'expectedDescription' => 'def',
            ],
            'empty-string' => [
                'rawValue' => '',
                'expectedValue' => null,
                'expectedDescription' => '',
            ],
            'multiple-separators' => [
                'rawValue' => 'abc|def|ghi',
                'expectedValue' => 'abc',
                'expectedDescription' => 'def|ghi',
            ],
        ];
    }
}
