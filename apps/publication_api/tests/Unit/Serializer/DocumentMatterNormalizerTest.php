<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Serializer;

use PHPUnit\Framework\TestCase;
use PublicationApi\Serializer\DocumentMatterNormalizer;
use Shared\ValueObject\DocumentMatter;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;

final class DocumentMatterNormalizerTest extends TestCase
{
    public function testNormalizeReturnsString(): void
    {
        $documentMatter = DocumentMatter::create('ABC-123');
        $result = new DocumentMatterNormalizer()->normalize($documentMatter);

        self::assertSame('ABC-123', $result);
    }

    public function testDenormalizeCreatesDocumentMatterFromValidString(): void
    {
        $result = new DocumentMatterNormalizer()->denormalize('ABC-123', DocumentMatter::class);

        self::assertSame('ABC-123', $result->toString());
    }

    public function testDenormalizeThrowsExceptionWhenDataIsNotAString(): void
    {
        $this->expectException(NotNormalizableValueException::class);

        new DocumentMatterNormalizer()->denormalize(12345, DocumentMatter::class);
    }

    public function testDenormalizeThrowsExceptionWhenValueContainsInvalidCharacters(): void
    {
        $this->expectException(NotNormalizableValueException::class);

        new DocumentMatterNormalizer()->denormalize('abc@invalid', DocumentMatter::class);
    }

    public function testSupportsNormalizationForDocumentMatterInstances(): void
    {
        $result = new DocumentMatterNormalizer()->supportsNormalization(DocumentMatter::create('abc-123'));

        self::assertTrue($result);
    }

    public function testDoesNotSupportNormalizationForNonDocumentMatterValues(): void
    {
        $result = new DocumentMatterNormalizer()->supportsNormalization('abc-123');

        self::assertFalse($result);
    }

    public function testSupportsDenormalizationForDocumentMatterType(): void
    {
        $result = new DocumentMatterNormalizer()->supportsDenormalization('abc-123', DocumentMatter::class);

        self::assertTrue($result);
    }
}
