<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Mockery;
use Shared\Doctrine\LandingPageSlugType;
use Shared\Domain\Publication\Subject\LandingPageSlug;
use Shared\Tests\Unit\UnitTestCase;

final class LandingPageSlugTypeTest extends UnitTestCase
{
    public function testItReturnsTheCorrectSqlDeclaration(): void
    {
        $platform = Mockery::mock(AbstractPlatform::class);

        $result = new LandingPageSlugType()->getSQLDeclaration([], $platform);

        self::assertSame('VARCHAR(50)', $result);
    }

    public function testItConvertsStringToPHPValue(): void
    {
        $platform = Mockery::mock(AbstractPlatform::class);

        $result = new LandingPageSlugType()->convertToPHPValue('Foo-Bar', $platform);

        self::assertInstanceOf(LandingPageSlug::class, $result);
        self::assertSame('foo-bar', $result->toString());
    }

    public function testItConvertsNullToPHPValue(): void
    {
        $platform = Mockery::mock(AbstractPlatform::class);

        $result = new LandingPageSlugType()->convertToPHPValue(null, $platform);

        self::assertNull($result);
    }

    public function testItConvertsLandingPageSlugToDatabaseValue(): void
    {
        $platform = Mockery::mock(AbstractPlatform::class);

        $result = new LandingPageSlugType()->convertToDatabaseValue(LandingPageSlug::create('Foo-Bar'), $platform);

        self::assertSame('foo-bar', $result);
    }

    public function testItConvertsNullToDatabaseValue(): void
    {
        $platform = Mockery::mock(AbstractPlatform::class);

        $result = new LandingPageSlugType()->convertToDatabaseValue(null, $platform);

        self::assertNull($result);
    }

    public function testItReturnsTheCorrectName(): void
    {
        self::assertSame('landing_page_slug', new LandingPageSlugType()->getName());
    }
}
