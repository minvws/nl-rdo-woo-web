<?php

declare(strict_types=1);

namespace Shared\ValueObject;

use InvalidArgumentException;
use Shared\Domain\Exception\OrganisationPrefixInvalidFormatException;
use Shared\Domain\Exception\OrganisationPrefixTooLongException;
use Shared\Domain\Exception\OrganisationPrefixTooShortException;
use Stringable;
use Webmozart\Assert\Assert;

use function strtoupper;
use function trim;

final readonly class OrganisationPrefix implements Stringable, Equatable
{
    public const int MIN_LENGTH = 5;
    public const int MAX_LENGTH = 30;
    public const string PATTERN = '^[0-9A-Za-z-]+$';

    private function __construct(
        private string $prefix,
    ) {
    }

    public static function create(string $prefix): self
    {
        $prefix = trim($prefix);

        try {
            Assert::minLength($prefix, self::MIN_LENGTH);
        } catch (InvalidArgumentException) {
            throw new OrganisationPrefixTooShortException(self::MIN_LENGTH);
        }

        try {
            Assert::maxLength($prefix, self::MAX_LENGTH);
        } catch (InvalidArgumentException) {
            throw new OrganisationPrefixTooLongException(self::MAX_LENGTH);
        }

        if (! RegexPattern::create(self::PATTERN)->matches($prefix)) {
            throw new OrganisationPrefixInvalidFormatException();
        }

        return new self(strtoupper($prefix));
    }

    public function __toString(): string
    {
        return $this->prefix;
    }

    public function toString(): string
    {
        return $this->__toString();
    }

    public function equalTo(mixed $other): bool
    {
        return $other instanceof self && $this->prefix === $other->prefix;
    }
}
