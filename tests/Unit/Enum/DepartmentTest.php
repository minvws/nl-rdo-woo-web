<?php

declare(strict_types=1);

namespace App\Tests\Unit\Enum;

use App\Enum\Department;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class DepartmentTest extends MockeryTestCase
{
    #[DataProvider('getTryFromShortTagData')]
    public function testTryFromShortTag(string $input, ?Department $expected): void
    {
        self::assertSame($expected, Department::tryFromShortTag($input));
    }

    public static function getTryFromShortTagData(): array
    {
        return [
            'DEF' => [
                'input' => 'Def',
                'expected' => Department::DEF,
            ],
            'FIN' => [
                'input' => 'Fin',
                'expected' => Department::FIN,
            ],
            'IW' => [
                'input' => 'I&W',
                'expected' => Department::IW,
            ],
            'JV' => [
                'input' => 'J&V',
                'expected' => Department::JV,
            ],
            'VWS' => [
                'input' => 'VWS',
                'expected' => Department::VWS,
            ],
            'EZK' => [
                'input' => 'EZK',
                'expected' => Department::EZK,
            ],
            'unknown' => [
                'input' => 'unknown',
                'expected' => null,
            ],
        ];
    }

    #[DataProvider('getTryFromNameData')]
    public function testTryFromName(string $input, ?Department $expected): void
    {
        self::assertSame($expected, Department::tryFromName($input));
    }

    public static function getTryFromNameData(): array
    {
        return [
            'DEF' => [
                'input' => 'Ministerie van Defensie',
                'expected' => Department::DEF,
            ],
            'FIN' => [
                'input' => 'Ministerie van Financiën',
                'expected' => Department::FIN,
            ],
            'VWS' => [
                'input' => 'Ministerie van Volksgezondheid, Welzijn en Sport',
                'expected' => Department::VWS,
            ],
            'VWS lowercase' => [
                'input' => 'ministerie van volksgezondheid, welzijn en sport',
                'expected' => Department::VWS,
            ],
            'EZK' => [
                'input' => 'Ministerie van Economische Zaken en Klimaat',
                'expected' => Department::EZK,
            ],
            'unknown' => [
                'input' => 'unknown',
                'expected' => null,
            ],
        ];
    }

    #[DataProvider('getTryFromNameAndShortTagData')]
    public function testTryFromNameOrShortTag(string $input, ?Department $expected): void
    {
        self::assertSame($expected, Department::tryFromNameOrShortTag($input));
    }

    public static function getTryFromNameAndShortTagData(): array
    {
        return [
            ...array_values(self::getTryFromNameData()),
            ...array_values(self::getTryFromShortTagData()),
        ];
    }

    #[DataProvider('getEqualsShouldReturnTrueData')]
    public function testEqualsShouldReturnTrue(string $inputName, Department $inputDepartment, bool $expected): void
    {
        self::assertSame($expected, $inputDepartment->equals($inputName));
    }

    public static function getEqualsShouldReturnTrueData(): array
    {
        return [
            'tag of matching department' => [
                'inputName' => 'VWS',
                'inputDepartment' => Department::VWS,
                'expected' => true,
            ],
            'name of matching department' => [
                'inputName' => 'Ministerie van Volksgezondheid, Welzijn en Sport',
                'inputDepartment' => Department::VWS,
                'expected' => true,
            ],
        ];
    }

    #[DataProvider('getEqualsShouldReturnFalseData')]
    public function testEqualsShouldReturnFalse(string $inputName, Department $inputDepartment, bool $expected): void
    {
        self::assertSame($expected, $inputDepartment->equals($inputName));
    }

    public static function getEqualsShouldReturnFalseData(): array
    {
        return [
            'tag of non-matching department' => [
                'inputName' => 'VWS',
                'inputDepartment' => Department::BZK,
                'expected' => false,
            ],
            'name of non-matching department' => [
                'inputName' => 'Ministerie van Volksgezondheid, Welzijn en Sport',
                'inputDepartment' => Department::DEF,
                'expected' => false,
            ],
            '"random" input #1' => [
                'inputName' => 'unknown',
                'inputDepartment' => Department::VWS,
                'expected' => false,
            ],
            '"random" input #2' => [
                'inputName' => 'Muggle Liaison Office',
                'inputDepartment' => Department::OCW,
                'expected' => false,
            ],
            'empty string input' => [
                'inputName' => '',
                'inputDepartment' => Department::VWS,
                'expected' => false,
            ],
        ];
    }
}
