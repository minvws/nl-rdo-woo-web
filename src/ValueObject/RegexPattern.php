<?php

declare(strict_types=1);

namespace Shared\ValueObject;

use InvalidArgumentException;

use function preg_match;
use function sprintf;

final readonly class RegexPattern
{
    private function __construct(
        private string $regex,
    ) {
    }

    public static function create(string $pattern): self
    {
        $regex = sprintf('/%s/', $pattern);

        if (@preg_match($regex, '') === false) {
            throw new InvalidArgumentException('Invalid regex pattern');
        }

        return new self($regex);
    }

    public function matches(string $subject): bool
    {
        return preg_match($this->regex, $subject) === 1;
    }
}
