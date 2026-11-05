<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\RegexPattern;

class RegexPatternTest extends UnitTestCase
{
    public function testMatchesWithMatchingSubject(): void
    {
        $subject = $this->getFaker()->lexify('????????');

        $this->assertTrue(RegexPattern::create('^[a-z]+$')->matches($subject));
    }

    public function testMatchesWithNonMatchingSubject(): void
    {
        $subject = $this->getFaker()->numerify('########');

        $this->assertFalse(RegexPattern::create('^[a-z]+$')->matches($subject));
    }

    #[DataProvider('invalidPatternDataProvider')]
    public function testCreateWithInvalidPattern(string $pattern): void
    {
        $this->expectException(InvalidArgumentException::class);

        RegexPattern::create($pattern);
    }

    /**
     * @return array<string, array{pattern: string}>
     */
    public static function invalidPatternDataProvider(): array
    {
        return [
            'unclosed character class' => ['pattern' => '^[a-z+$'],
            'unclosed group' => ['pattern' => '^(a-z$'],
            'slash is not allowed' => ['pattern' => '^a/z$'],
        ];
    }
}
