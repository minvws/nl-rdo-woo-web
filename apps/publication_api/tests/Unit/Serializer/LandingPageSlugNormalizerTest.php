<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Serializer;

use PHPUnit\Framework\Attributes\DataProvider;
use PublicationApi\Serializer\LandingPageSlugNormalizer;
use Shared\Domain\Publication\Subject\LandingPageSlug;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;

final class LandingPageSlugNormalizerTest extends UnitTestCase
{
    public function testItNormalizesLandingPageSlug(): void
    {
        $slug = LandingPageSlug::create('foo-bar');
        $result = new LandingPageSlugNormalizer()->normalize($slug);

        self::assertSame('foo-bar', $result);
    }

    public function testItDenormalizesString(): void
    {
        $result = new LandingPageSlugNormalizer()->denormalize('Foo-Bar', LandingPageSlug::class);

        self::assertInstanceOf(LandingPageSlug::class, $result);
        self::assertSame('foo-bar', (string) $result);
    }

    public function testItThrowsOnNonStringDenormalization(): void
    {
        $this->expectException(NotNormalizableValueException::class);

        new LandingPageSlugNormalizer()->denormalize(12345, LandingPageSlug::class);
    }

    public function testItThrowsOnInvalidSlug(): void
    {
        $this->expectException(NotNormalizableValueException::class);

        new LandingPageSlugNormalizer()->denormalize('a', LandingPageSlug::class);
    }

    public function testItSupportsNormalizationOfLandingPageSlug(): void
    {
        $result = new LandingPageSlugNormalizer()->supportsNormalization(LandingPageSlug::create('foo-bar'));

        self::assertTrue($result);
    }

    public function testItDoesNotSupportNormalizationOfOtherTypes(): void
    {
        $result = new LandingPageSlugNormalizer()->supportsNormalization('foo-bar');

        self::assertFalse($result);
    }

    public function testItSupportsDenormalizationOfLandingPageSlugType(): void
    {
        $result = new LandingPageSlugNormalizer()->supportsDenormalization('foo-bar', LandingPageSlug::class);

        self::assertTrue($result);
    }

    public function testItDoesNotSupportDenormalizationOfOtherTypes(): void
    {
        $result = new LandingPageSlugNormalizer()->supportsDenormalization('foo-bar', stdClass::class);

        self::assertFalse($result);
    }

    public function testGetSupportedTypesReturnsMappingForLandingPageSlug(): void
    {
        $result = new LandingPageSlugNormalizer()->getSupportedTypes(null);

        self::assertSame([LandingPageSlug::class => true], $result);
    }

    #[DataProvider('invalidDenormalizationValueProvider')]
    public function testItThrowsOnInvalidDenormalizationValue(mixed $value): void
    {
        $this->expectException(NotNormalizableValueException::class);

        new LandingPageSlugNormalizer()->denormalize($value, LandingPageSlug::class);
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
            'array' => [['slug']],
            'object' => [new stdClass()],
        ];
    }
}
