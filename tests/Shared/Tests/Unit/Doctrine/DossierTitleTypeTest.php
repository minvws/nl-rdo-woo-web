<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Mockery;
use Shared\Doctrine\DossierTitleType;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;

final class DossierTitleTypeTest extends UnitTestCase
{
    public function testItReturnsTheCorrectSqlDeclarationWithDefaultLength(): void
    {
        $platform = Mockery::mock(AbstractPlatform::class);
        $result = new DossierTitleType()->getSQLDeclaration([], $platform);

        self::assertSame('VARCHAR(500)', $result);
    }

    public function testItConvertsStringToPHPValue(): void
    {
        $platform = Mockery::mock(AbstractPlatform::class);

        $result = new DossierTitleType()->convertToPHPValue('Some dossier title', $platform);

        self::assertInstanceOf(DossierTitle::class, $result);
        self::assertSame('Some dossier title', (string) $result);
    }

    public function testItConvertsDossierTitleToDatabaseValue(): void
    {
        $platform = Mockery::mock(AbstractPlatform::class);

        $result = new DossierTitleType()->convertToDatabaseValue(DossierTitle::create('Some dossier title'), $platform);

        self::assertSame('Some dossier title', $result);
    }

    public function testItReturnsTheCorrectName(): void
    {
        $result = new DossierTitleType()->getName();

        self::assertSame('dossier_title', $result);
    }
}
