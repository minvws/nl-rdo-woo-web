<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\ExternalId;
use Shared\ValueObject\PublicationContext;

use function str_repeat;

class PublicationContextTest extends UnitTestCase
{
    public function testFromString(): void
    {
        $value = 'some-context_1.0~test';

        $publicationContext = PublicationContext::fromString($value);

        self::assertSame($value, $publicationContext->toString());
    }

    public function testFromUnnormalizedString(): void
    {
        $publicationContext = PublicationContext::fromUnnormalizedString('Some-CONTEXT_1.0~Test');

        self::assertSame('some-context_1.0~test', $publicationContext->toString());
    }

    public function testToString(): void
    {
        $value = 'some-context_1.0~test';

        $publicationContext = PublicationContext::fromString($value);

        self::assertSame($value, (string) $publicationContext);
    }

    public function testEqualToReturnsTrueForSameValue(): void
    {
        $publicationContext = PublicationContext::fromString('some-context');

        self::assertTrue($publicationContext->equalTo(PublicationContext::fromUnnormalizedString('Some-Context')));
    }

    public function testEqualToReturnsFalseForDifferentValue(): void
    {
        $publicationContext = PublicationContext::fromString('some-context');

        self::assertFalse($publicationContext->equalTo(PublicationContext::fromString('other-context')));
    }

    #[DataProvider('otherTypeDataProvider')]
    public function testEqualToReturnsFalseForOtherTypes(mixed $other): void
    {
        self::assertFalse(PublicationContext::fromString('some-context')->equalTo($other));
    }

    /**
     * @return array<string, array{other: mixed}>
     */
    public static function otherTypeDataProvider(): array
    {
        return [
            'null' => ['other' => null],
            'string with same value' => ['other' => 'some-context'],
            'other equatable with same value' => ['other' => ExternalId::create('some-context')],
        ];
    }

    #[DataProvider('invalidValueProvider')]
    public function testFromStringWithInvalidValue(string $value, int $expectedErrorCode): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode($expectedErrorCode);

        PublicationContext::fromString($value);
    }

    /**
     * @return array<string, array{0: string, 1: int}>
     */
    public static function invalidValueProvider(): array
    {
        return [
            'empty string' => ['', PublicationContext::ERROR_EMPTY],
            'invalid characters' => ['invalid!', PublicationContext::ERROR_INVALID_FORMAT],
            'too long' => [str_repeat('a', PublicationContext::MAX_LENGTH + 1), PublicationContext::ERROR_INVALID_LENGTH],
            'not lowercase' => ['Some-Context', PublicationContext::ERROR_NOT_LOWERCASE],
        ];
    }
}
