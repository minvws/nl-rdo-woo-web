<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Serializer;

use Shared\Serializer\UrlNormalizer;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\Url;
use stdClass;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;

final class UrlNormalizerTest extends UnitTestCase
{
    public function testItNormalizesUrl(): void
    {
        $url = $this->getFaker()->url();

        $result = new UrlNormalizer()->normalize(Url::create($url));

        self::assertSame($url, $result);
    }

    public function testItDenormalizesString(): void
    {
        $url = $this->getFaker()->url();

        $result = new UrlNormalizer()->denormalize($url, Url::class);

        self::assertSame($url, $result->toString());
    }

    public function testItThrowsOnNonStringDenormalization(): void
    {
        $this->expectException(NotNormalizableValueException::class);

        new UrlNormalizer()->denormalize($this->getFaker()->randomNumber(), Url::class);
    }

    public function testItThrowsOnInvalidUrl(): void
    {
        $this->expectException(NotNormalizableValueException::class);

        new UrlNormalizer()->denormalize($this->getFaker()->word(), Url::class);
    }

    public function testItSupportsNormalizationOfUrl(): void
    {
        $result = new UrlNormalizer()->supportsNormalization(Url::create($this->getFaker()->url()));

        self::assertTrue($result);
    }

    public function testItDoesNotSupportNormalizationOfOtherTypes(): void
    {
        $result = new UrlNormalizer()->supportsNormalization($this->getFaker()->url());

        self::assertFalse($result);
    }

    public function testItSupportsDenormalizationOfUrlType(): void
    {
        $result = new UrlNormalizer()->supportsDenormalization($this->getFaker()->url(), Url::class);

        self::assertTrue($result);
    }

    public function testItDoesNotSupportDenormalizationOfOtherTypes(): void
    {
        $result = new UrlNormalizer()->supportsDenormalization($this->getFaker()->url(), stdClass::class);

        self::assertFalse($result);
    }

    public function testGetSupportedTypesReturnsMappingForUrl(): void
    {
        $result = new UrlNormalizer()->getSupportedTypes(null);

        self::assertSame([Url::class => true], $result);
    }
}
