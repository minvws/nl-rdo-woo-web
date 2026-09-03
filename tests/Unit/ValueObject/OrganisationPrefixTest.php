<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Domain\Exception\OrganisationPrefixArgumentException;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\OrganisationPrefix;

use function str_repeat;

final class OrganisationPrefixTest extends UnitTestCase
{
    public function testCreateNormalizesToUppercase(): void
    {
        $prefix = OrganisationPrefix::create('abc-12');

        self::assertSame('ABC-12', $prefix->toString());
        self::assertSame('ABC-12', (string) $prefix);
    }

    public function testPrefixesAreEqualWhenTheirNormalizedValuesMatch(): void
    {
        self::assertTrue(
            OrganisationPrefix::create('abc-12')->equalTo(OrganisationPrefix::create('ABC-12')),
        );
    }

    public function testPrefixesAreNotEqualWhenTheirValuesDiffer(): void
    {
        self::assertFalse(
            OrganisationPrefix::create('abc-12')->equalTo(OrganisationPrefix::create('abc-13')),
        );
    }

    #[DataProvider('invalidPrefixDataProvider')]
    public function testCreateRejectsInvalidValues(string $prefix, string $translationKey): void
    {
        try {
            OrganisationPrefix::create($prefix);
            self::fail('Expected OrganisationPrefixArgumentException to be thrown');
        } catch (OrganisationPrefixArgumentException $exception) {
            self::assertSame($translationKey, $exception->getTranslationKey());
        }
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function invalidPrefixDataProvider(): array
    {
        return [
            'empty' => ['', 'organisation.prefix_too_short'],
            'too short' => ['ABCD', 'organisation.prefix_too_short'],
            'too long' => [
                str_repeat('A', OrganisationPrefix::MAX_LENGTH + 1),
                'organisation.prefix_too_long',
            ],
            'contains a space' => ['ABC 12', 'organisation.prefix_invalid_format'],
            'contains a slash' => ['ABC/12', 'organisation.prefix_invalid_format'],
        ];
    }
}
