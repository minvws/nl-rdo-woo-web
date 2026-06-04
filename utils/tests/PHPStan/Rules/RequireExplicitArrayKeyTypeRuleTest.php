<?php

declare(strict_types=1);

namespace Utils\Tests\PHPStan\Rules;

use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rules\RequireExplicitArrayKeyTypeRule;

/**
 * @extends RuleTestCase<RequireExplicitArrayKeyTypeRule>
 */
final class RequireExplicitArrayKeyTypeRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new RequireExplicitArrayKeyTypeRule(
            self::getContainer()->getByType(Lexer::class),
            self::getContainer()->getByType(PhpDocParser::class),
        );
    }

    public function testValid(): void
    {
        $this->analyse(
            [__DIR__ . '/data/require-explicit-array-key-type-valid.php'],
            [],
        );
    }

    public function testErrors(): void
    {
        $this->analyse(
            [__DIR__ . '/data/require-explicit-array-key-type-errors.php'],
            [
                [
                    'PHPDoc type "array<string>" is missing an explicit key type. For example use array<array-key, string>.',
                    5,
                ],
                [
                    'PHPDoc type "array<int>" is missing an explicit key type. For example use array<array-key, int>.',
                    14,
                ],
                [
                    'PHPDoc type "array<SomeClass>" is missing an explicit key type. For example use array<array-key, SomeClass>.',
                    22,
                ],
                [
                    'PHPDoc type "array<array<string>>" is missing an explicit key type. For example use array<array-key, array<string>>.',
                    27,
                ],
                [
                    'PHPDoc type "array<string>" is missing an explicit key type. For example use array<array-key, string>.',
                    27,
                ],
                [
                    'PHPDoc type "array<string>" is missing an explicit key type. For example use array<array-key, string>.',
                    36,
                ],
                [
                    'PHPDoc type "array<string>" is missing an explicit key type. For example use array<array-key, string>.',
                    41,
                ],
                [
                    'PHPDoc type "array<string>" is missing an explicit key type. For example use array<array-key, string>.',
                    44,
                ],
                [
                    'PHPDoc type "array<string>" is missing an explicit key type. For example use array<array-key, string>.',
                    52,
                ],
            ],
        );
    }
}
