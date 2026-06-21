<?php

declare(strict_types=1);

namespace Shared\ValueObject;

use InvalidArgumentException;
use Stringable;

use function mb_strlen;
use function preg_match;
use function strtolower;

final readonly class DocumentId implements Stringable
{
    public const string PATTERN = '/^[a-zA-Z0-9.-]*$/';
    public const int MIN_LENGTH = 1;
    public const int MAX_LENGTH = 170;

    public const int ERROR_EMPTY = 1;
    public const int ERROR_INVALID_FORMAT = 2;
    public const int ERROR_INVALID_LENGTH = 3;

    private function __construct(
        private string $id,
    ) {
    }

    public function __toString(): string
    {
        return strtolower($this->id);
    }

    public static function create(string $id): self
    {
        if ($id === '') {
            throw new InvalidArgumentException('Document ID cannot be empty', self::ERROR_EMPTY);
        }

        if (preg_match(self::PATTERN, $id) !== 1) {
            throw new InvalidArgumentException('Invalid document ID format', self::ERROR_INVALID_FORMAT);
        }

        $stringLength = mb_strlen($id);
        if ($stringLength < self::MIN_LENGTH || $stringLength > self::MAX_LENGTH) {
            throw new InvalidArgumentException('Invalid document ID length', self::ERROR_INVALID_LENGTH);
        }

        return new self($id);
    }

    public function toString(): string
    {
        return strtolower($this->id);
    }
}
