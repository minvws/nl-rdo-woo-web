<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Serializer;

use PHPUnit\Framework\Attributes\DataProvider;
use PublicationApi\Serializer\DossierTitleNormalizer;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use stdClass;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;

final class DossierTitleNormalizerTest extends UnitTestCase
{
    public function testItNormalizesDossierTitle(): void
    {
        $title = DossierTitle::create('Test title');
        $result = new DossierTitleNormalizer()->normalize($title);

        self::assertSame('Test title', $result);
    }

    public function testItDenormalizesString(): void
    {
        $result = new DossierTitleNormalizer()->denormalize('Test title', DossierTitle::class);

        self::assertSame('Test title', (string) $result);
    }

    public function testItThrowsOnNonStringDenormalization(): void
    {
        $this->expectException(NotNormalizableValueException::class);

        new DossierTitleNormalizer()->denormalize(12345, DossierTitle::class);
    }

    public function testItThrowsOnInvalidTitle(): void
    {
        $this->expectException(NotNormalizableValueException::class);

        new DossierTitleNormalizer()->denormalize('a', DossierTitle::class);
    }

    public function testItSupportsNormalizationOfDossierTitle(): void
    {
        $result = new DossierTitleNormalizer()->supportsNormalization(DossierTitle::create('Test title'));

        self::assertTrue($result);
    }

    public function testItDoesNotSupportNormalizationOfOtherTypes(): void
    {
        $result = new DossierTitleNormalizer()->supportsNormalization('Test title');

        self::assertFalse($result);
    }

    public function testItSupportsDenormalizationOfDossierTitleType(): void
    {
        $result = new DossierTitleNormalizer()->supportsDenormalization('Test title', DossierTitle::class);

        self::assertTrue($result);
    }

    public function testItDoesNotSupportDenormalizationOfOtherTypes(): void
    {
        $result = new DossierTitleNormalizer()->supportsDenormalization('Test title', stdClass::class);

        self::assertFalse($result);
    }

    public function testGetSupportedTypesReturnsMappingForDossierTitle(): void
    {
        $result = new DossierTitleNormalizer()->getSupportedTypes(null);

        self::assertSame([DossierTitle::class => true], $result);
    }

    #[DataProvider('invalidDenormalizationValueProvider')]
    public function testItThrowsOnInvalidDenormalizationValue(mixed $value): void
    {
        $this->expectException(NotNormalizableValueException::class);

        new DossierTitleNormalizer()->denormalize($value, DossierTitle::class);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public static function invalidDenormalizationValueProvider(): array
    {
        return [
            'integer' => [12345],
            'float' => [12.5],
            'boolean' => [true],
            'array' => [['title']],
            'object' => [new stdClass()],
        ];
    }
}
