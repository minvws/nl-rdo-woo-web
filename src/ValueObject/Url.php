<?php

declare(strict_types=1);

namespace Shared\ValueObject;

use InvalidArgumentException;
use Stringable;

use function filter_var;

use const FILTER_VALIDATE_URL;

final readonly class Url implements Stringable
{
    private function __construct(
        private string $url,
    ) {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('invalid url');
        }
    }

    public function __toString(): string
    {
        return $this->url;
    }

    public static function create(string $url): self
    {
        return new self($url);
    }

    public function toString(): string
    {
        return $this->__toString();
    }
}
