<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Form\Transformer;

use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Domain\Publication\Subject\LandingPageSlug;
use Shared\Form\Transformer\StringToLandingPageSlugTransformer;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

use function str_repeat;

class StringToLandingPageSlugTransformerTest extends UnitTestCase
{
    private StringToLandingPageSlugTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new StringToLandingPageSlugTransformer();
    }

    public function testTransformReturnsEmptyStringWhenValueIsNull(): void
    {
        $this->assertSame('', $this->transformer->transform(null));
    }

    public function testTransformReturnsSlugAsString(): void
    {
        $slug = $this->getFaker()->regexify('[a-z]{10,20}');

        $this->assertSame($slug, $this->transformer->transform(LandingPageSlug::create($slug)));
    }

    public function testTransformThrowsExceptionForInvalidType(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessageIs('Expected LandingPageSlug, got int');

        $this->transformer->transform(42);
    }

    public function testReverseTransformReturnsSlugForValidString(): void
    {
        $slug = $this->getFaker()->regexify('[a-z]{10,20}');
        $result = $this->transformer->reverseTransform($slug);

        $this->assertInstanceOf(LandingPageSlug::class, $result);
        $this->assertSame($slug, $result->toString());
    }

    public function testReverseTransformLowercasesTheSlug(): void
    {
        $this->assertSame('some-slug', $this->transformer->reverseTransform('Some-Slug')->toString());
    }

    public function testReverseTransformThrowsExceptionWhenValueIsEmptyString(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessageIs('Landing page slug cannot be empty');

        $this->transformer->reverseTransform('');
    }

    public function testReverseTransformThrowsExceptionWhenValueIsNull(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessageIs('Landing page slug cannot be empty');

        $this->transformer->reverseTransform(null);
    }

    public function testReverseTransformThrowsExceptionForNonStringValue(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessageIs('Expected string, got int');

        $this->transformer->reverseTransform(123);
    }

    #[DataProvider('invalidSlugDataProvider')]
    public function testReverseTransformSetsTranslationKeyOnException(string $slug, string $expectedMessageKey): void
    {
        try {
            $this->transformer->reverseTransform($slug);
            $this->fail('Expected TransformationFailedException to be thrown');
        } catch (TransformationFailedException $e) {
            $this->assertSame($expectedMessageKey, $e->getInvalidMessage());

            $params = $e->getInvalidMessageParameters();
            $this->assertSame(LandingPageSlug::MIN_LENGTH, $params['{{ min }}']);
            $this->assertSame(LandingPageSlug::MAX_LENGTH, $params['{{ max }}']);
        }
    }

    /**
     * @return array<string, list<string>>
     */
    public static function invalidSlugDataProvider(): array
    {
        return [
            'contains a space' => ['some slug', 'subject_landing_page_slug_invalid_format'],
            'contains a slash' => ['some/slug', 'subject_landing_page_slug_invalid_format'],
            'too short' => ['a', 'subject_landing_page_slug_invalid_length'],
            'too long' => [str_repeat('a', LandingPageSlug::MAX_LENGTH + 1), 'subject_landing_page_slug_invalid_length'],
        ];
    }
}
