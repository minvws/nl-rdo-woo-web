<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Serializer;

use Shared\Serializer\DocumentNumberNormalizer;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentNumber;
use stdClass;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;

final class DocumentNumberNormalizerTest extends UnitTestCase
{
    public function testItNormalizesADocumentNumberToItsExactString(): void
    {
        $normalizer = new DocumentNumberNormalizer();

        self::assertSame(
            'Prefix-Matter_01-Doc.123',
            $normalizer->normalize(DocumentNumber::fromString('Prefix-Matter_01-Doc.123')),
        );
    }

    public function testItDenormalizesAStringWithoutChangingIt(): void
    {
        $normalizer = new DocumentNumberNormalizer();

        $result = $normalizer->denormalize('Prefix-Matter_01-Doc.123', DocumentNumber::class);

        self::assertInstanceOf(DocumentNumber::class, $result);
        self::assertSame('Prefix-Matter_01-Doc.123', $result->toString());
    }

    public function testItRejectsNonStringInputDuringDenormalization(): void
    {
        $this->expectException(NotNormalizableValueException::class);

        new DocumentNumberNormalizer()->denormalize(123, DocumentNumber::class);
    }

    public function testItSupportsNormalizationOfDocumentNumber(): void
    {
        self::assertTrue(
            new DocumentNumberNormalizer()->supportsNormalization(DocumentNumber::fromString('document-123')),
        );
    }

    public function testItDoesNotSupportNormalizationOfOtherTypes(): void
    {
        self::assertFalse(new DocumentNumberNormalizer()->supportsNormalization('document-123'));
    }

    public function testItSupportsDenormalizationOfDocumentNumberType(): void
    {
        self::assertTrue(
            new DocumentNumberNormalizer()->supportsDenormalization('document-123', DocumentNumber::class),
        );
    }

    public function testItDoesNotSupportDenormalizationOfOtherTypes(): void
    {
        self::assertFalse(
            new DocumentNumberNormalizer()->supportsDenormalization('document-123', stdClass::class),
        );
    }

    public function testGetSupportedTypesReturnsMappingForDocumentNumber(): void
    {
        self::assertSame(
            [DocumentNumber::class => true],
            new DocumentNumberNormalizer()->getSupportedTypes(null),
        );
    }
}
