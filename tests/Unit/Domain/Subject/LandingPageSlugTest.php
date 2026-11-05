<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Subject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Domain\Publication\Subject\LandingPageSlug;
use Shared\Domain\Publication\Subject\LandingPageTitle;
use Shared\Tests\Unit\UnitTestCase;

use function str_repeat;

final class LandingPageSlugTest extends UnitTestCase
{
    public function testCreateNormalizesToLowercase(): void
    {
        $slug = LandingPageSlug::create('Foo-Bar');

        self::assertSame('foo-bar', $slug->toString());
        self::assertSame('foo-bar', (string) $slug);
    }

    public function testCreateAcceptsGeneratedSlug(): void
    {
        $slug = LandingPageSlug::create((string) $this->getFaker()->landingPageSlug());

        self::assertInstanceOf(LandingPageSlug::class, $slug);
        self::assertSame($slug->toString(), (string) $slug);
    }

    public function testEqualToReturnsTrueForSameNormalizedValue(): void
    {
        self::assertTrue(LandingPageSlug::create('foo-bar')->equalTo(LandingPageSlug::create('Foo-Bar')));
    }

    public function testEqualToReturnsFalseForDifferentValue(): void
    {
        self::assertFalse(LandingPageSlug::create('foo-bar')->equalTo(LandingPageSlug::create('foo-baz')));
    }

    #[DataProvider('otherTypeDataProvider')]
    public function testEqualToReturnsFalseForOtherTypes(mixed $other): void
    {
        self::assertFalse(LandingPageSlug::create('foo-bar')->equalTo($other));
    }

    /**
     * @return array<string, array{other: mixed}>
     */
    public static function otherTypeDataProvider(): array
    {
        return [
            'null' => ['other' => null],
            'string with same value' => ['other' => 'foo-bar'],
            'other equatable with same value' => ['other' => LandingPageTitle::create('foo-bar')],
        ];
    }

    #[DataProvider('invalidSlugDataProvider')]
    public function testCreateWithInvalidValueThrowsException(string $value, int $expectedErrorCode): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode($expectedErrorCode);

        LandingPageSlug::create($value);
    }

    /**
     * @return array<string, array{value: string, expectedErrorCode: int}>
     */
    public static function invalidSlugDataProvider(): array
    {
        return [
            'empty string' => [
                'value' => '',
                'expectedErrorCode' => LandingPageSlug::ERROR_EMPTY,
            ],
            'single character' => [
                'value' => 'a',
                'expectedErrorCode' => LandingPageSlug::ERROR_INVALID_LENGTH,
            ],
            'too long' => [
                'value' => str_repeat('a', LandingPageSlug::MAX_LENGTH + 1),
                'expectedErrorCode' => LandingPageSlug::ERROR_INVALID_LENGTH,
            ],
            'whitespace' => [
                'value' => 'foo bar',
                'expectedErrorCode' => LandingPageSlug::ERROR_INVALID_FORMAT,
            ],
            'underscore' => [
                'value' => 'foo_bar',
                'expectedErrorCode' => LandingPageSlug::ERROR_INVALID_FORMAT,
            ],
        ];
    }
}
