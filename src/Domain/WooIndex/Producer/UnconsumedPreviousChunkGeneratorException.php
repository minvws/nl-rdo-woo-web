<?php

declare(strict_types=1);

namespace Shared\Domain\WooIndex\Producer;

use Shared\Domain\WooIndex\Exception\WooIndexException;

final class UnconsumedPreviousChunkGeneratorException extends \LogicException implements WooIndexException
{
    public static function create(): self
    {
        return new self('Previous chunk generator must be consumed first!');
    }
}
