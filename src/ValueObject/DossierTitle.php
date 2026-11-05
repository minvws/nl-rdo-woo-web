<?php

declare(strict_types=1);

namespace Shared\ValueObject;

use InvalidArgumentException;
use Shared\Domain\Exception\DossierTitleTooLongException;
use Shared\Domain\Exception\DossierTitleTooShortException;
use Stringable;
use Webmozart\Assert\Assert;

use function trim;

final readonly class DossierTitle implements Stringable, Equatable
{
    public const int MIN_LENGTH = 3;
    public const int MAX_LENGTH = 500;

    private function __construct(
        private string $title,
    ) {
    }

    public function __toString(): string
    {
        return $this->title;
    }

    public static function create(string $title): self
    {
        try {
            Assert::minLength(trim($title), self::MIN_LENGTH);
        } catch (InvalidArgumentException) {
            throw new DossierTitleTooShortException(self::MIN_LENGTH);
        }

        try {
            Assert::maxLength($title, self::MAX_LENGTH);
        } catch (InvalidArgumentException) {
            throw new DossierTitleTooLongException(self::MAX_LENGTH);
        }

        return new self($title);
    }

    public function toString(): string
    {
        return $this->__toString();
    }

    public function equalTo(mixed $other): bool
    {
        return $other instanceof self && $this->title === $other->title;
    }
}
