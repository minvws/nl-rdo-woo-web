<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Subject;

use InvalidArgumentException;
use Stringable;

use function mb_strlen;

final readonly class LandingPageTitle implements Stringable
{
    public const int MIN_LENGTH = 1;
    public const int MAX_LENGTH = 200;

    public const int ERROR_EMPTY = 1;
    public const int ERROR_INVALID_LENGTH = 2;

    private function __construct(
        private string $title,
    ) {
    }

    public static function create(string $title): self
    {
        if ($title === '') {
            throw new InvalidArgumentException('Landing page title cannot be empty', self::ERROR_EMPTY);
        }

        $stringLength = mb_strlen($title);
        if ($stringLength < self::MIN_LENGTH || $stringLength > self::MAX_LENGTH) {
            throw new InvalidArgumentException('Invalid landing page title length', self::ERROR_INVALID_LENGTH);
        }

        return new self($title);
    }

    public function __toString(): string
    {
        return $this->title;
    }

    public function toString(): string
    {
        return $this->title;
    }
}
