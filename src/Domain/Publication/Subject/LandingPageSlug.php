<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Subject;

use InvalidArgumentException;
use Stringable;

use function mb_strlen;
use function preg_match;
use function strtolower;

final readonly class LandingPageSlug implements Stringable
{
    public const string PATTERN = '/^[A-Za-z0-9-]+$/';
    public const int MIN_LENGTH = 2;
    public const int MAX_LENGTH = 50;

    public const int ERROR_EMPTY = 1;
    public const int ERROR_INVALID_FORMAT = 2;
    public const int ERROR_INVALID_LENGTH = 3;

    private function __construct(
        private string $slug,
    ) {
    }

    public static function create(string $slug): self
    {
        if ($slug === '') {
            throw new InvalidArgumentException('Landing page slug cannot be empty', self::ERROR_EMPTY);
        }

        if (preg_match(self::PATTERN, $slug) !== 1) {
            throw new InvalidArgumentException('Invalid landing page slug format', self::ERROR_INVALID_FORMAT);
        }

        $stringLength = mb_strlen($slug);
        if ($stringLength < self::MIN_LENGTH || $stringLength > self::MAX_LENGTH) {
            throw new InvalidArgumentException('Invalid landing page slug length', self::ERROR_INVALID_LENGTH);
        }

        return new self(strtolower($slug));
    }

    public function __toString(): string
    {
        return $this->slug;
    }

    public function toString(): string
    {
        return $this->slug;
    }
}
