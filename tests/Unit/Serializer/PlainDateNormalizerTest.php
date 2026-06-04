<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Serializer;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Shared\Serializer\PlainDateNormalizer;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;

final class PlainDateNormalizerTest extends TestCase
{
    public function testItNormalizesPlainDate(): void
    {
        $date = PlainDate::create('2024-01-15');
        $result = new PlainDateNormalizer()->normalize($date);

        self::assertSame('2024-01-15', $result);
    }

    public function testItDenormalizesString(): void
    {
        $result = new PlainDateNormalizer()->denormalize('2024-01-15', PlainDate::class);

        self::assertSame('2024-01-15', (string) $result);
    }

    public function testItThrowsOnNonStringDenormalization(): void
    {
        $this->expectException(NotNormalizableValueException::class);

        new PlainDateNormalizer()->denormalize(12345, PlainDate::class);
    }

    public function testItSupportsNormalizationOfPlainDate(): void
    {
        $result = new PlainDateNormalizer()->supportsNormalization(PlainDate::create('2024-01-15'));

        self::assertTrue($result);
    }

    public function testItDoesNotSupportNormalizationOfOtherTypes(): void
    {
        $result = new PlainDateNormalizer()->supportsNormalization('2024-01-15');

        self::assertFalse($result);
    }

    public function testItSupportsDenormalizationOfPlainDateType(): void
    {
        $result = new PlainDateNormalizer()->supportsDenormalization('2024-01-15', PlainDate::class);

        self::assertTrue($result);
    }

    public function testItDoesNotSupportDenormalizationOfOtherTypes(): void
    {
        $result = new PlainDateNormalizer()->supportsDenormalization('2024-01-15', DateTimeImmutable::class);

        self::assertFalse($result);
    }

    public function testGetSupportedTypesReturnsMappingForPlainDate(): void
    {
        $result = new PlainDateNormalizer()->getSupportedTypes(null);

        self::assertSame([PlainDate::class => true], $result);
    }

    public function testGetDeserializationPathReturnsPathFromContext(): void
    {
        $result = new PlainDateNormalizer()->getDeserializationPath(['deserialization_path' => 'date']);

        self::assertSame('date', $result);
    }

    public function testGetDeserializationPathReturnsNullWhenNotInContext(): void
    {
        $result = new PlainDateNormalizer()->getDeserializationPath([]);

        self::assertNull($result);
    }

    public function testGetDeserializationPathReturnsNullWhenPathIsNotString(): void
    {
        $result = new PlainDateNormalizer()->getDeserializationPath(['deserialization_path' => 123]);

        self::assertNull($result);
    }
}
