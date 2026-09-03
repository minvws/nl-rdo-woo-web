<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Mockery;
use Shared\Doctrine\OrganisationPrefixType;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\OrganisationPrefix;

final class OrganisationPrefixTypeTest extends UnitTestCase
{
    public function testItReturnsTheCorrectSqlDeclarationWithDefaultLength(): void
    {
        $platform = Mockery::mock(AbstractPlatform::class);

        self::assertSame('VARCHAR(30)', new OrganisationPrefixType()->getSQLDeclaration([], $platform));
    }

    public function testItConvertsStringToPhpValue(): void
    {
        $platform = Mockery::mock(AbstractPlatform::class);

        $result = new OrganisationPrefixType()->convertToPHPValue('abc-12', $platform);

        self::assertInstanceOf(OrganisationPrefix::class, $result);
        self::assertSame('ABC-12', (string) $result);
    }

    public function testItConvertsNullToPhpValue(): void
    {
        $platform = Mockery::mock(AbstractPlatform::class);

        self::assertNull(new OrganisationPrefixType()->convertToPHPValue(null, $platform));
    }

    public function testItConvertsOrganisationPrefixToDatabaseValue(): void
    {
        $platform = Mockery::mock(AbstractPlatform::class);

        self::assertSame(
            'ABC-12',
            new OrganisationPrefixType()->convertToDatabaseValue(OrganisationPrefix::create('abc-12'), $platform),
        );
    }

    public function testItConvertsNullToDatabaseValue(): void
    {
        $platform = Mockery::mock(AbstractPlatform::class);

        self::assertNull(new OrganisationPrefixType()->convertToDatabaseValue(null, $platform));
    }

    public function testItReturnsTheCorrectName(): void
    {
        self::assertSame('organisation_prefix', new OrganisationPrefixType()->getName());
    }
}
