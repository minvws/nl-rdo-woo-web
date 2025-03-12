<?php

declare(strict_types=1);

namespace App\Domain\WooIndex\Producer;

use App\Domain\WooIndex\DiWooException;

final class InvalidChunkSizeException extends \InvalidArgumentException implements DiWooException
{
    public static function create(): self
    {
        return new self('Allowed chunk sizes are between 1 and 50_000');
    }
}
