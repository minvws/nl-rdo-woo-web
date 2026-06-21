<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Form\Transformer;

use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Form\Transformer\StringToDossierTitleTransformer;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Symfony\Component\Form\Exception\TransformationFailedException;

use function str_repeat;

class StringToDossierTitleTransformerTest extends UnitTestCase
{
    private StringToDossierTitleTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new StringToDossierTitleTransformer();
    }

    public function testTransformReturnsEmptyStringWhenValueIsNull(): void
    {
        $this->assertSame('', $this->transformer->transform(null));
    }

    public function testTransformReturnsDossierTitleAsString(): void
    {
        $title = $this->getFaker()->regexify('[a-z]{10,20}');
        $dossierTitle = DossierTitle::create($title);

        $this->assertSame($title, $this->transformer->transform($dossierTitle));
    }

    public function testTransformThrowsExceptionForInvalidType(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessageIs('Expected DossierTitle, got int');

        $this->transformer->transform(42);
    }

    public function testReverseTransformThrowsExceptionWhenValueIsEmptyString(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessageIs('dossier.title_required');

        $this->transformer->reverseTransform('');
    }

    public function testReverseTransformThrowsExceptionWhenValueIsNull(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessageIs('dossier.title_required');

        $this->transformer->reverseTransform(null);
    }

    public function testReverseTransformReturnsDossierTitleForValidString(): void
    {
        $title = $this->getFaker()->regexify('[a-z]{10,20}');
        $result = $this->transformer->reverseTransform($title);

        $this->assertInstanceOf(DossierTitle::class, $result);
        $this->assertSame($title, (string) $result);
    }

    public function testReverseTransformThrowsExceptionForNonStringValue(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessageIs('Expected string, got integer');

        $this->transformer->reverseTransform(123);
    }

    #[DataProvider('invalidTitleDataProvider')]
    public function testReverseTransformThrowsExceptionForInvalidTitle(string $title, string $expectedMessage): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessageIs($expectedMessage);

        $this->transformer->reverseTransform($title);
    }

    #[DataProvider('invalidTitleDataProvider')]
    public function testReverseTransformSetsCorrectionMetadataOnException(string $title, string $expectedMessageKey): void
    {
        try {
            $this->transformer->reverseTransform($title);
            $this->fail('Expected TransformationFailedException to be thrown');
        } catch (TransformationFailedException $e) {
            // Verify the translation key is set as the message
            $this->assertSame($expectedMessageKey, $e->getMessage());

            // Verify invalidMessage contains the translation key
            $this->assertSame($expectedMessageKey, $e->getInvalidMessage());

            // Verify invalidMessageParameters contain the {{ limit }} placeholder with correct value
            $params = $e->getInvalidMessageParameters();
            $this->assertArrayHasKey('{{ limit }}', $params);

            if ($expectedMessageKey === 'dossier.title_too_short') {
                $this->assertSame(3, $params['{{ limit }}']);
            } elseif ($expectedMessageKey === 'dossier.title_too_long') {
                $this->assertSame(500, $params['{{ limit }}']);
            }
        }
    }

    /**
     * @return array<string, list<string>>
     */
    public static function invalidTitleDataProvider(): array
    {
        return [
            'too short (1 char)' => ['a', 'dossier.title_too_short'],
            'too short (2 chars)' => ['ab', 'dossier.title_too_short'],
            'too short (trimmed to 2)' => [' ab', 'dossier.title_too_short'],
            'too long (501 chars)' => [str_repeat('a', 501), 'dossier.title_too_long'],
        ];
    }
}
