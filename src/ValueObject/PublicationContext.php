<?php

declare(strict_types=1);

namespace Shared\ValueObject;

use InvalidArgumentException;
use Stringable;

use function mb_strlen;
use function strtolower;

final readonly class PublicationContext implements Equatable, Stringable
{
    public const string PATTERN = '^[A-Za-z0-9\-._~]*$';
    public const int MIN_LENGTH = 1;
    public const int MAX_LENGTH = 255;

    public const int ERROR_EMPTY = 1;
    public const int ERROR_INVALID_FORMAT = 2;
    public const int ERROR_INVALID_LENGTH = 3;
    public const int ERROR_NOT_LOWERCASE = 4;

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

        if (! RegexPattern::create(self::PATTERN)->matches($value)) {
            throw new InvalidArgumentException('Invalid publication context format', self::ERROR_INVALID_FORMAT);
        }

        $stringLength = mb_strlen($value);
        if ($stringLength < self::MIN_LENGTH || $stringLength > self::MAX_LENGTH) {
            throw new InvalidArgumentException('Invalid publication context length', self::ERROR_INVALID_LENGTH);
        }

        if ($value !== strtolower($value)) {
            throw new InvalidArgumentException('Publication context must be lowercase', self::ERROR_NOT_LOWERCASE);
        }

        return new self($value);
    }

    public static function fromUnnormalizedString(string $value): self
    {
        return self::fromString(strtolower($value));
    }

    public function equalTo(mixed $other): bool
    {
        return $other instanceof self && $this->value === $other->value;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
