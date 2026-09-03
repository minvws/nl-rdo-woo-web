<?php

declare(strict_types=1);

namespace Shared\ValueObject;

use InvalidArgumentException;
use Shared\Domain\Exception\OrganisationPrefixArgumentException;
use Stringable;
use Webmozart\Assert\Assert;

use function preg_match;
use function strtoupper;

/**
 * @implements Equatable<OrganisationPrefix>
 */
final readonly class OrganisationPrefix implements Stringable, Equatable
{
    public const int MIN_LENGTH = 5;
    public const int MAX_LENGTH = 30;
    public const string PATTERN = '/^[0-9A-Za-z-]+$/';

    private function __construct(
        private string $prefix,
    ) {
    }

    public static function create(string $prefix): self
    {
        try {
            Assert::minLength($prefix, self::MIN_LENGTH);
        } catch (InvalidArgumentException) {
            throw new OrganisationPrefixArgumentException(
                'organisation.prefix_too_short',
                ['{{ limit }}' => self::MIN_LENGTH],
            );
        }

        try {
            Assert::maxLength($prefix, self::MAX_LENGTH);
        } catch (InvalidArgumentException) {
            throw new OrganisationPrefixArgumentException(
                'organisation.prefix_too_long',
                ['{{ limit }}' => self::MAX_LENGTH],
            );
        }

        if (preg_match(self::PATTERN, $prefix) !== 1) {
            throw new OrganisationPrefixArgumentException('organisation.prefix_invalid_format');
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

    public function equalTo(Equatable $other): bool
    {
        return $this->toString() === $other->toString();
    }
}
