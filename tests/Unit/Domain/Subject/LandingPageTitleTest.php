<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Subject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Domain\Publication\Subject\LandingPageTitle;
use Shared\Tests\Unit\UnitTestCase;

use function str_repeat;

final class LandingPageTitleTest extends UnitTestCase
{
    public function testCreateReturnsTitle(): void
    {
        $title = LandingPageTitle::create('Landing page title');

        self::assertSame('Landing page title', $title->toString());
        self::assertSame('Landing page title', (string) $title);
    }

    public function testToStringReturnsTitle(): void
    {
        $title = LandingPageTitle::create('Some title');

        self::assertSame('Some title', (string) $title);
    }

    #[DataProvider('invalidTitleDataProvider')]
    public function testCreateWithInvalidValueThrowsException(string $value, int $expectedErrorCode): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode($expectedErrorCode);

        LandingPageTitle::create($value);
    }

    /**
     * @return array<string, array{value: string, expectedErrorCode: int}>
     */
    public static function invalidTitleDataProvider(): array
    {
        return [
            'empty string' => [
                'value' => '',
                'expectedErrorCode' => LandingPageTitle::ERROR_EMPTY,
            ],
            'too long' => [
                'value' => str_repeat('a', LandingPageTitle::MAX_LENGTH + 1),
                'expectedErrorCode' => LandingPageTitle::ERROR_INVALID_LENGTH,
            ],
        ];
    }
}
