<?php

declare(strict_types=1);

namespace Shared\ValueObject;

use InvalidArgumentException;
use Stringable;

use function mb_strlen;

final readonly class ExternalId implements Stringable, Equatable
{
    public const int MAX_LENGTH = 128;
    public const int MIN_LENGTH = 1;
    public const string PATTERN = '^[A-Za-z0-9\-._~]*$';

    private function __construct(
        private string $id,
    ) {
    }

    public function __toString(): string
    {
        return $this->id;
    }

    public static function create(string $id): self
    {
        if (! RegexPattern::create(self::PATTERN)->matches($id)) {
            throw new InvalidArgumentException('Invalid external ID format');
        }

        $stringLength = mb_strlen($id);
        if ($stringLength < self::MIN_LENGTH || $stringLength > self::MAX_LENGTH) {
            throw new InvalidArgumentException('Invalid external id length');
        }

        return new self($id);
    }

    public function toString(): string
    {
        return $this->__toString();
    }

    public function equalTo(mixed $other): bool
    {
        return $other instanceof self && $this->id === $other->id;
    }
}
