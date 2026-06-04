<?php

declare(strict_types=1);

namespace Shared\ValueObject;

use InvalidArgumentException;
use Stringable;

use function mb_strlen;
use function pathinfo;
use function preg_match;

use const PATHINFO_EXTENSION;

final readonly class FileName implements Stringable
{
    private const string PATTERN = '/^[A-Za-z0-9 ._-]+$/';

    private function __construct(
        private string $filename,
    ) {
    }

    public function __toString(): string
    {
        return $this->filename;
    }

    public static function create(string $filename): self
    {
        if (mb_strlen($filename) === 0) {
            throw new InvalidArgumentException('Filename must not be empty');
        }

        if (mb_strlen($filename) > 255) {
            throw new InvalidArgumentException('Filename must not exceed 255 characters');
        }

        if (preg_match(self::PATTERN, $filename) !== 1) {
            throw new InvalidArgumentException('Filename contains invalid characters');
        }

        return new self($filename);
    }

    public function getExtension(): string
    {
        return pathinfo($this->filename, PATHINFO_EXTENSION);
    }

    public function toString(): string
    {
        return $this->__toString();
    }
}
