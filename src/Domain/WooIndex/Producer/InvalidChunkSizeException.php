<?php

declare(strict_types=1);

namespace Shared\Domain\WooIndex\Producer;

use InvalidArgumentException;
use Shared\Domain\WooIndex\Exception\WooIndexException;

final class InvalidChunkSizeException extends InvalidArgumentException implements WooIndexException
{
    public static function create(): self
    {
        return new self('Allowed chunk sizes are between 1 and 50_000');
    }
}
