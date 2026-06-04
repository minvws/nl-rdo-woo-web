<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Shared\Doctrine\PlainDateType;
use Shared\ValueObject\PlainDate;

final class PlainDateTypeTest extends MockeryTestCase
{
    public function testItReturnsTheCorrectSqlDeclaration(): void
    {
        $platform = Mockery::mock(AbstractPlatform::class);
        $result = new PlainDateType()->getSQLDeclaration([], $platform);

        self::assertSame('DATE', $result);
    }

    public function testItConvertsStringToPHPValue(): void
    {
        $platform = Mockery::mock(AbstractPlatform::class);

        $result = new PlainDateType()->convertToPHPValue('2024-01-15', $platform);

        self::assertInstanceOf(PlainDate::class, $result);
        self::assertSame('2024-01-15', (string) $result);
    }

    public function testItConvertsNullToPHPValue(): void
    {
        $platform = Mockery::mock(AbstractPlatform::class);
        $result = new PlainDateType()->convertToPHPValue(null, $platform);

        self::assertNull($result);
    }

    public function testItConvertsPlainDateToDatabaseValue(): void
    {
        $platform = Mockery::mock(AbstractPlatform::class);

        $result = new PlainDateType()->convertToDatabaseValue(PlainDate::create('2024-01-15'), $platform);

        self::assertSame('2024-01-15', $result);
    }

    public function testItConvertsNullToDatabaseValue(): void
    {
        $platform = Mockery::mock(AbstractPlatform::class);
        $result = new PlainDateType()->convertToDatabaseValue(null, $platform);

        self::assertNull($result);
    }

    public function testItReturnsTheCorrectName(): void
    {
        $result = new PlainDateType()->getName();

        self::assertSame('plain_date', $result);
    }
}
