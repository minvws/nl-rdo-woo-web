<?php

declare(strict_types=1);

namespace Shared\ValueObject;

use InvalidArgumentException;
use Stringable;

use function mb_strlen;
use function preg_match;

/**
 * @implements Equatable<PublicationContext>
 */
final readonly class PublicationContext implements Equatable, Stringable
{
    public const string PATTERN = '/^[A-Za-z0-9\-._~]*$/';
    public const int MIN_LENGTH = 1;
    public const int MAX_LENGTH = 255;

    public const int ERROR_EMPTY = 1;
    public const int ERROR_INVALID_FORMAT = 2;
    public const int ERROR_INVALID_LENGTH = 3;

    private function __construct(
        private string $value,
    ) {
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public static function fromString(string $value): self
    {
        if ($value === '') {
            throw new InvalidArgumentException('Publication context cannot be empty', self::ERROR_EMPTY);
        }

        if (preg_match(self::PATTERN, $value) !== 1) {
            throw new InvalidArgumentException('Invalid publication context format', self::ERROR_INVALID_FORMAT);
        }

        $stringLength = mb_strlen($value);
        if ($stringLength < self::MIN_LENGTH || $stringLength > self::MAX_LENGTH) {
            throw new InvalidArgumentException('Invalid publication context length', self::ERROR_INVALID_LENGTH);
        }

        return new self($value);
    }

    /**
     * @param PublicationContext $other
     */
    public function equalTo(Equatable $other): bool
    {
        return $this->toString() === $other->toString();
    }

    public function toString(): string
    {
        return $this->value;
    }
}
