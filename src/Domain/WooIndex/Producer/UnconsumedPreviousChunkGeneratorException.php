<?php

declare(strict_types=1);

namespace App\Domain\WooIndex\Producer;

use App\Domain\WooIndex\DiWooException;

final class UnconsumedPreviousChunkGeneratorException extends \LogicException implements DiWooException
{
    public static function create(): self
    {
        return new self('Previous chunk generator must be consumed first!');
    }
}
