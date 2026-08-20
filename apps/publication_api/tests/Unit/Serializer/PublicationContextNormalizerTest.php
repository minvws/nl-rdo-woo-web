<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Serializer;

use PHPUnit\Framework\TestCase;
use PublicationApi\Serializer\PublicationContextNormalizer;
use Shared\ValueObject\PublicationContext;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;

final class PublicationContextNormalizerTest extends TestCase
{
    public function testNormalizeReturnsString(): void
    {
        $publicationContext = PublicationContext::fromString('ABC-123');
        $result = new PublicationContextNormalizer()->normalize($publicationContext);

        self::assertSame('ABC-123', $result);
    }

    public function testDenormalizeCreatesPublicationContextFromValidString(): void
    {
        $result = new PublicationContextNormalizer()->denormalize('ABC-123', PublicationContext::class);

        self::assertSame('ABC-123', $result->toString());
    }

    public function testDenormalizeThrowsExceptionWhenDataIsNotAString(): void
    {
        $this->expectException(NotNormalizableValueException::class);

        new PublicationContextNormalizer()->denormalize(12345, PublicationContext::class);
    }

    public function testDenormalizeThrowsExceptionWhenValueContainsInvalidCharacters(): void
    {
        $this->expectException(NotNormalizableValueException::class);

        new PublicationContextNormalizer()->denormalize('abc@invalid', PublicationContext::class);
    }

    public function testSupportsNormalizationForPublicationContextInstances(): void
    {
        $result = new PublicationContextNormalizer()->supportsNormalization(PublicationContext::fromString('abc-123'));

        self::assertTrue($result);
    }

    public function testDoesNotSupportNormalizationForNonPublicationContextValues(): void
    {
        $result = new PublicationContextNormalizer()->supportsNormalization('abc-123');

        self::assertFalse($result);
    }

    public function testSupportsDenormalizationForPublicationContextType(): void
    {
        $result = new PublicationContextNormalizer()->supportsDenormalization('abc-123', PublicationContext::class);

        self::assertTrue($result);
    }
}
