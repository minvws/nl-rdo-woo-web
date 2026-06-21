<?php

declare(strict_types=1);

namespace Shared\ValueObject;

use InvalidArgumentException;
use Shared\Domain\Exception\DossierTitleArgumentException;
use Stringable;
use Webmozart\Assert\Assert;

use function trim;

final readonly class DossierTitle implements Stringable
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
            throw new DossierTitleArgumentException(
                'dossier.title_too_short',
                ['{{ limit }}' => self::MIN_LENGTH],
            );
        }

        try {
            Assert::maxLength($title, self::MAX_LENGTH);
        } catch (InvalidArgumentException) {
            throw new DossierTitleArgumentException(
                'dossier.title_too_long',
                ['{{ limit }}' => self::MAX_LENGTH],
            );
        }

        return new self($title);
    }

    public function toString(): string
    {
        return $this->__toString();
    }
}
