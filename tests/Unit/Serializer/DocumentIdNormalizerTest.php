<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Serializer;

use PHPUnit\Framework\TestCase;
use Shared\Serializer\DocumentIdNormalizer;
use Shared\ValueObject\DocumentId;
use stdClass;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;

final class DocumentIdNormalizerTest extends TestCase
{
    public function testItNormalizesDocumentId(): void
    {
        $documentId = DocumentId::create('abc123');
        $result = new DocumentIdNormalizer()->normalize($documentId);

        self::assertSame('abc123', $result);
    }

    public function testItDenormalizesString(): void
    {
        $result = new DocumentIdNormalizer()->denormalize('abc123', DocumentId::class);

        self::assertSame('abc123', (string) $result);
    }

    public function testItThrowsOnNonStringDenormalization(): void
    {
        $this->expectException(NotNormalizableValueException::class);

        new DocumentIdNormalizer()->denormalize(12345, DocumentId::class);
    }

    public function testItThrowsOnInvalidDocumentId(): void
    {
        $this->expectException(NotNormalizableValueException::class);

        new DocumentIdNormalizer()->denormalize('INVALID_ID', DocumentId::class);
    }

    public function testItSupportsNormalizationOfDocumentId(): void
    {
        $result = new DocumentIdNormalizer()->supportsNormalization(DocumentId::create('abc123'));

        self::assertTrue($result);
    }

    public function testItDoesNotSupportNormalizationOfOtherTypes(): void
    {
        $result = new DocumentIdNormalizer()->supportsNormalization('abc123');

        self::assertFalse($result);
    }

    public function testItSupportsDenormalizationOfDocumentIdType(): void
    {
        $result = new DocumentIdNormalizer()->supportsDenormalization('abc123', DocumentId::class);

        self::assertTrue($result);
    }

    public function testItDoesNotSupportDenormalizationOfOtherTypes(): void
    {
        $result = new DocumentIdNormalizer()->supportsDenormalization('abc123', stdClass::class);

        self::assertFalse($result);
    }

    public function testGetSupportedTypesReturnsMappingForDocumentId(): void
    {
        $result = new DocumentIdNormalizer()->getSupportedTypes(null);

        self::assertSame([DocumentId::class => true], $result);
    }

    public function testGetDeserializationPathReturnsPathFromContext(): void
    {
        $result = new DocumentIdNormalizer()->getPathFromContext(['deserialization_path' => 'documentId']);

        self::assertSame('documentId', $result);
    }

    public function testGetDeserializationPathReturnsNullWhenNotInContext(): void
    {
        $result = new DocumentIdNormalizer()->getPathFromContext([]);

        self::assertNull($result);
    }

    public function testGetDeserializationPathReturnsNullWhenPathIsNotString(): void
    {
        $result = new DocumentIdNormalizer()->getPathFromContext(['deserialization_path' => 123]);

        self::assertNull($result);
    }
}
