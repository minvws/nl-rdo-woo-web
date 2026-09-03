<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Mockery;
use Shared\Doctrine\LandingPageTitleType;
use Shared\Domain\Publication\Subject\LandingPageTitle;
use Shared\Tests\Unit\UnitTestCase;

final class LandingPageTitleTypeTest extends UnitTestCase
{
    public function testItReturnsTheCorrectSqlDeclaration(): void
    {
        $platform = Mockery::mock(AbstractPlatform::class);

        $result = new LandingPageTitleType()->getSQLDeclaration([], $platform);

        self::assertSame('VARCHAR(200)', $result);
    }

    public function testItConvertsStringToPHPValue(): void
    {
        $platform = Mockery::mock(AbstractPlatform::class);

        $result = new LandingPageTitleType()->convertToPHPValue('Landing page title', $platform);

        self::assertInstanceOf(LandingPageTitle::class, $result);
        self::assertSame('Landing page title', $result->toString());
    }

    public function testItConvertsNullToPHPValue(): void
    {
        $platform = Mockery::mock(AbstractPlatform::class);

        $result = new LandingPageTitleType()->convertToPHPValue(null, $platform);

        self::assertNull($result);
    }

    public function testItConvertsLandingPageTitleToDatabaseValue(): void
    {
        $platform = Mockery::mock(AbstractPlatform::class);

        $result = new LandingPageTitleType()->convertToDatabaseValue(LandingPageTitle::create('Landing page title'), $platform);

        self::assertSame('Landing page title', $result);
    }

    public function testItConvertsNullToDatabaseValue(): void
    {
        $platform = Mockery::mock(AbstractPlatform::class);

        $result = new LandingPageTitleType()->convertToDatabaseValue(null, $platform);

        self::assertNull($result);
    }

    public function testItReturnsTheCorrectName(): void
    {
        self::assertSame('landing_page_title', new LandingPageTitleType()->getName());
    }
}
