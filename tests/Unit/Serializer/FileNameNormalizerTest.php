<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Serializer;

use PHPUnit\Framework\TestCase;
use Shared\Serializer\FileNameNormalizer;
use Shared\ValueObject\FileName;
use stdClass;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;

final class FileNameNormalizerTest extends TestCase
{
    public function testItNormalizesFilename(): void
    {
        $filename = FileName::create('document.pdf');
        $result = new FileNameNormalizer()->normalize($filename);

        self::assertSame('document.pdf', $result);
    }

    public function testItDenormalizesString(): void
    {
        $result = new FileNameNormalizer()->denormalize('document.pdf', FileName::class);

        self::assertSame('document.pdf', (string) $result);
    }

    public function testItThrowsOnNonStringDenormalization(): void
    {
        $this->expectException(NotNormalizableValueException::class);

        new FileNameNormalizer()->denormalize(12345, FileName::class);
    }

    public function testItThrowsOnInvalidFilename(): void
    {
        $this->expectException(NotNormalizableValueException::class);

        new FileNameNormalizer()->denormalize('../secret.txt', FileName::class);
    }

    public function testItSupportsNormalizationOfFilename(): void
    {
        $result = new FileNameNormalizer()->supportsNormalization(FileName::create('document.pdf'));

        self::assertTrue($result);
    }

    public function testItDoesNotSupportNormalizationOfOtherTypes(): void
    {
        $result = new FileNameNormalizer()->supportsNormalization('document.pdf');

        self::assertFalse($result);
    }

    public function testItSupportsDenormalizationOfFilenameType(): void
    {
        $result = new FileNameNormalizer()->supportsDenormalization('document.pdf', FileName::class);

        self::assertTrue($result);
    }

    public function testItDoesNotSupportDenormalizationOfOtherTypes(): void
    {
        $result = new FileNameNormalizer()->supportsDenormalization('document.pdf', stdClass::class);

        self::assertFalse($result);
    }

    public function testGetSupportedTypesReturnsMappingForFilename(): void
    {
        $result = new FileNameNormalizer()->getSupportedTypes(null);

        self::assertSame([FileName::class => true], $result);
    }

    public function testGetDeserializationPathReturnsPathFromContext(): void
    {
        $result = new FileNameNormalizer()->getPathFromContext(['deserialization_path' => 'fileName']);

        self::assertSame('fileName', $result);
    }

    public function testGetDeserializationPathReturnsNullWhenNotInContext(): void
    {
        $result = new FileNameNormalizer()->getPathFromContext([]);

        self::assertNull($result);
    }

    public function testGetDeserializationPathReturnsNullWhenPathIsNotString(): void
    {
        $result = new FileNameNormalizer()->getPathFromContext(['deserialization_path' => 123]);

        self::assertNull($result);
    }
}
