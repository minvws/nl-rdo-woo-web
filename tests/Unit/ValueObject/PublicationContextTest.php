<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Tests\Unit\UnitTestCase;
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

    public function testToString(): void
    {
        $value = 'some-context_1.0~test';

        $publicationContext = PublicationContext::fromString($value);

        self::assertSame($value, (string) $publicationContext);
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
        ];
    }
}
