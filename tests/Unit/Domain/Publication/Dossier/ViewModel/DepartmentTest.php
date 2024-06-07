<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\ViewModel;

use App\Domain\Publication\Dossier\ViewModel\Department;
use App\Enum\Department as DepartmentEnum;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class DepartmentTest extends UnitTestCase
{
    #[DataProvider('getIsDepartmentData')]
    public function testIsDeparatment(string $nameInput, DepartmentEnum $value, bool $expected): void
    {
        $department = new Department(name: $nameInput);

        $this->assertSame($expected, $department->isDepartment($value));
    }

    /**
     * @return array<string,array{nameInput:string,value:DepartmentEnum,expected:bool}>
     */
    public static function getIsDepartmentData(): array
    {
        return [
            'invalid department without a match' => [
                'nameInput' => 'random name',
                'value' => DepartmentEnum::VWS,
                'expected' => false,
            ],
            'valid department with a match' => [
                'nameInput' => DepartmentEnum::VWS->value,
                'value' => DepartmentEnum::VWS,
                'expected' => true,
            ],
            'valid department without a match' => [
                'nameInput' => DepartmentEnum::VWS->value,
                'value' => DepartmentEnum::BZK,
                'expected' => false,
            ],
        ];
    }
}
