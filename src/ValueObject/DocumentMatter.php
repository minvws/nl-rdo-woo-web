<?php

declare(strict_types=1);

namespace Shared\ValueObject;

use InvalidArgumentException;
use Stringable;

use function mb_strlen;
use function preg_match;

final readonly class DocumentMatter implements Stringable
{
    public const string PATTERN = '/^[a-zA-Z0-9-._~]*$/';
    public const int MIN_LENGTH = 1;
    public const int MAX_LENGTH = 255;

    public const int ERROR_EMPTY = 1;
    public const int ERROR_INVALID_CHARACTERS = 2;
    public const int ERROR_INVALID_LENGTH = 3;

    private function __construct(
        private string $documentMatter,
    ) {
    }

    public function __toString(): string
    {
        return $this->documentMatter;
    }

    public static function create(string $documentMatter): self
    {
        if ($documentMatter === '') {
            throw new InvalidArgumentException('DocumentMatter cannot be empty', self::ERROR_EMPTY);
        }

        if (preg_match(self::PATTERN, $documentMatter) !== 1) {
            throw new InvalidArgumentException('DocumentMatter contains invalid characters', self::ERROR_INVALID_CHARACTERS);
        }

        $stringLength = mb_strlen($documentMatter);
        if ($stringLength < self::MIN_LENGTH || $stringLength > self::MAX_LENGTH) {
            throw new InvalidArgumentException('DocumentMatter has invalid length', self::ERROR_INVALID_LENGTH);
        }

        return new self($documentMatter);
    }

    public function toString(): string
    {
        return $this->__toString();
    }
}
