<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Serializer;

use PHPUnit\Framework\Attributes\DataProvider;
use PublicationApi\Serializer\LandingPageTitleNormalizer;
use Shared\Domain\Publication\Subject\LandingPageTitle;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;

final class LandingPageTitleNormalizerTest extends UnitTestCase
{
    public function testItNormalizesLandingPageTitle(): void
    {
        $title = LandingPageTitle::create('Test title');
        $result = new LandingPageTitleNormalizer()->normalize($title);

        self::assertSame('Test title', $result);
    }

    public function testItDenormalizesString(): void
    {
        $result = new LandingPageTitleNormalizer()->denormalize('T', LandingPageTitle::class);

        self::assertSame('T', (string) $result);
    }

    public function testItThrowsOnNonStringDenormalization(): void
    {
        $this->expectException(NotNormalizableValueException::class);

        new LandingPageTitleNormalizer()->denormalize(12345, LandingPageTitle::class);
    }

    public function testItThrowsOnInvalidTitle(): void
    {
        $this->expectException(NotNormalizableValueException::class);

        new LandingPageTitleNormalizer()->denormalize('', LandingPageTitle::class);
    }

    public function testItSupportsNormalizationOfLandingPageTitle(): void
    {
        $result = new LandingPageTitleNormalizer()->supportsNormalization(LandingPageTitle::create('Test title'));

        self::assertTrue($result);
    }

    public function testItDoesNotSupportNormalizationOfOtherTypes(): void
    {
        $result = new LandingPageTitleNormalizer()->supportsNormalization('Test title');

        self::assertFalse($result);
    }

    public function testItSupportsDenormalizationOfLandingPageTitleType(): void
    {
        $result = new LandingPageTitleNormalizer()->supportsDenormalization('Test title', LandingPageTitle::class);

        self::assertTrue($result);
    }

    public function testItDoesNotSupportDenormalizationOfOtherTypes(): void
    {
        $result = new LandingPageTitleNormalizer()->supportsDenormalization('Test title', stdClass::class);

        self::assertFalse($result);
    }

    public function testGetSupportedTypesReturnsMappingForLandingPageTitle(): void
    {
        $result = new LandingPageTitleNormalizer()->getSupportedTypes(null);

        self::assertSame([LandingPageTitle::class => true], $result);
    }

    #[DataProvider('invalidDenormalizationValueProvider')]
    public function testItThrowsOnInvalidDenormalizationValue(mixed $value): void
    {
        $this->expectException(NotNormalizableValueException::class);

        new LandingPageTitleNormalizer()->denormalize($value, LandingPageTitle::class);
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
