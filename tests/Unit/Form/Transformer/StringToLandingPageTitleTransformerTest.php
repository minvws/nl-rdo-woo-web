<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Form\Transformer;

use Shared\Domain\Publication\Subject\LandingPageTitle;
use Shared\Form\Transformer\StringToLandingPageTitleTransformer;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

use function str_repeat;

class StringToLandingPageTitleTransformerTest extends UnitTestCase
{
    private StringToLandingPageTitleTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new StringToLandingPageTitleTransformer();
    }

    public function testTransformReturnsEmptyStringWhenValueIsNull(): void
    {
        $this->assertSame('', $this->transformer->transform(null));
    }

    public function testTransformReturnsTitleAsString(): void
    {
        $title = $this->getFaker()->sentence();

        $this->assertSame($title, $this->transformer->transform(LandingPageTitle::create($title)));
    }

    public function testTransformThrowsExceptionForInvalidType(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessageIs('Expected LandingPageTitle, got int');

        $this->transformer->transform(42);
    }

    public function testReverseTransformReturnsTitleForValidString(): void
    {
        $title = $this->getFaker()->sentence();
        $result = $this->transformer->reverseTransform($title);

        $this->assertInstanceOf(LandingPageTitle::class, $result);
        $this->assertSame($title, $result->toString());
    }

    public function testReverseTransformThrowsExceptionWhenValueIsEmptyString(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessageIs('Landing page title cannot be empty');

        $this->transformer->reverseTransform('');
    }

    public function testReverseTransformThrowsExceptionWhenValueIsNull(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessageIs('Landing page title cannot be empty');

        $this->transformer->reverseTransform(null);
    }

    public function testReverseTransformThrowsExceptionForNonStringValue(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessageIs('Expected string, got int');

        $this->transformer->reverseTransform(123);
    }

    public function testReverseTransformSetsTranslationKeyForTooLongTitle(): void
    {
        try {
            $this->transformer->reverseTransform(str_repeat('a', LandingPageTitle::MAX_LENGTH + 1));
            $this->fail('Expected TransformationFailedException to be thrown');
        } catch (TransformationFailedException $e) {
            $this->assertSame('subject_landing_page_title_invalid_length', $e->getInvalidMessage());
            $this->assertSame(LandingPageTitle::MAX_LENGTH, $e->getInvalidMessageParameters()['{{ max }}']);
        }
    }
}
