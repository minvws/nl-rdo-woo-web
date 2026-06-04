<?php

declare(strict_types=1);

namespace Admin\Tests\Unit\Form\Transformer;

use Admin\Form\Transformer\RoleTransformer;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Tests\Unit\UnitTestCase;

class RoleTransformerTest extends UnitTestCase
{
    public function testTransformNullReturnsEmptyArray(): void
    {
        self::assertSame([], new RoleTransformer()->transform(null));
    }

    public function testTransformEmptyArrayReturnsEmptyArray(): void
    {
        self::assertSame([], new RoleTransformer()->transform([]));
    }

    #[DataProvider('transformProvider')]
    public function testTransformRole(string $input, string $expected): void
    {
        self::assertSame([$expected], new RoleTransformer()->transform([$input]));
    }

    /**
     * @return array<array-key, list<string>>
     */
    public static function transformProvider(): array
    {
        return [
            ['ROLE_ADMIN', 'admin'],
            ['ROLE_user', 'user'],
            ['ROLE_SuperAdmin', 'superadmin'],
            ['ADMIN', 'admin'],
        ];
    }

    public function testReverseTransformEmptyArrayReturnsEmptyArray(): void
    {
        self::assertSame([], new RoleTransformer()->reverseTransform([]));
    }

    #[DataProvider('reverseTransformProvider')]
    public function testReverseTransformRole(string $input, string $expected): void
    {
        self::assertSame([$expected], new RoleTransformer()->reverseTransform([$input]));
    }

    /**
     * @return array<array-key, list<string>>
     */
    public static function reverseTransformProvider(): array
    {
        return [
            ['admin', 'ROLE_ADMIN'],
            ['ADMIN', 'ROLE_ADMIN'],
            ['SuperAdmin', 'ROLE_SUPERADMIN'],
            ['', 'ROLE_'],
        ];
    }

    public function testTransformThrowsOnNonArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new RoleTransformer()->transform('not-an-array');
    }

    public function testTransformThrowsOnNonStringElements(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new RoleTransformer()->transform([1, 2, 3]);
    }

    public function testReverseTransformThrowsOnNonArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new RoleTransformer()->reverseTransform('not-an-array');
    }
}
