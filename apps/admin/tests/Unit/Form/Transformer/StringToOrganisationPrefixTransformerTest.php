<?php

declare(strict_types=1);

namespace Admin\Tests\Unit\Form\Transformer;

use Admin\Form\Transformer\StringToOrganisationPrefixTransformer;
use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\OrganisationPrefix;
use Symfony\Component\Form\Exception\TransformationFailedException;

use function str_repeat;

class StringToOrganisationPrefixTransformerTest extends UnitTestCase
{
    private StringToOrganisationPrefixTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new StringToOrganisationPrefixTransformer();
    }

    public function testTransformReturnsEmptyStringForNull(): void
    {
        self::assertSame('', $this->transformer->transform(null));
    }

    public function testTransformReturnsTheNormalisedPrefix(): void
    {
        self::assertSame(
            'ABC-12',
            $this->transformer->transform(OrganisationPrefix::create('abc-12')),
        );
    }

    public function testReverseTransformCreatesAnUppercaseValueObject(): void
    {
        $result = $this->transformer->reverseTransform('abc-12');

        self::assertInstanceOf(OrganisationPrefix::class, $result);
        self::assertSame('ABC-12', $result->toString());
    }

    #[DataProvider('invalidPrefixProvider')]
    public function testReverseTransformExposesTheDomainTranslationKey(
        string $value,
        string $translationKey,
    ): void {
        try {
            $this->transformer->reverseTransform($value);
            self::fail('Expected TransformationFailedException to be thrown');
        } catch (TransformationFailedException $exception) {
            self::assertSame($translationKey, $exception->getInvalidMessage());
        }
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function invalidPrefixProvider(): array
    {
        return [
            'empty' => ['', 'organisation.prefix_too_short'],
            'too short' => ['ABCD', 'organisation.prefix_too_short'],
            'too long' => [
                str_repeat('A', OrganisationPrefix::MAX_LENGTH + 1),
                'organisation.prefix_too_long',
            ],
            'invalid characters' => ['ABC 12', 'organisation.prefix_invalid_format'],
        ];
    }
}
