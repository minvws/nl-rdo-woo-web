<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Mockery;
use Shared\Doctrine\DocumentNumberType;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentNumber;

final class DocumentNumberTypeTest extends UnitTestCase
{
    public function testItReturnsTheTextColumnDeclaration(): void
    {
        $platform = Mockery::mock(AbstractPlatform::class);
        $platform->expects('getClobTypeDeclarationSQL')->with([])->andReturn('TEXT');

        self::assertSame('TEXT', new DocumentNumberType()->getSQLDeclaration([], $platform));
    }

    public function testItConvertsAStringToADocumentNumber(): void
    {
        $platform = Mockery::mock(AbstractPlatform::class);

        $result = new DocumentNumberType()->convertToPHPValue('prefix-doc-01', $platform);

        self::assertInstanceOf(DocumentNumber::class, $result);
        self::assertSame('prefix-doc-01', $result->toString());
    }

    public function testItConvertsADocumentNumberToAString(): void
    {
        $platform = Mockery::mock(AbstractPlatform::class);

        self::assertSame(
            'prefix-doc-01',
            new DocumentNumberType()->convertToDatabaseValue(
                DocumentNumber::fromString('prefix-doc-01'),
                $platform,
            ),
        );
    }

    public function testItExposesTheConfiguredName(): void
    {
        self::assertSame('document_number', new DocumentNumberType()->getName());
    }
}
