<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use Shared\TenantId;
use Shared\Tests\Unit\UnitTestCase;
use ValueError;

final class TenantIdTest extends UnitTestCase
{
    public function testTenantId(): void
    {
        $this->assertMatchesObjectSnapshot(TenantId::cases());
    }

    public function testTenantIdAsString(): void
    {
        $this->assertMatchesTextSnapshot(TenantId::asString());
    }

    #[DataProvider('getFromStringData')]
    public function testFromStringWithValidValues(string $input, TenantId $expected): void
    {
        $this->assertSame($expected, TenantId::fromString($input));
    }

    public function testFromStringWithInvalidValuesThrowsException(): void
    {
        $this->expectException(ValueError::class);

        TenantId::fromString('ACME');
    }

    /**
     * @return array<string,array{input:string,expected:TenantId}>
     */
    public static function getFromStringData(): array
    {
        return [
            'all lower case' => [
                'input' => 'minvws',
                'expected' => TenantId::MINVWS,
            ],
            'all upper case' => [
                'input' => 'MINVWS',
                'expected' => TenantId::MINVWS,
            ],
            'mixed cases' => [
                'input' => 'MinFin',
                'expected' => TenantId::MINFIN,
            ],
        ];
    }
}
