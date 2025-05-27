<?php

declare(strict_types=1);

namespace App\Domain\WooIndex\Exception;

final class WooIndexInvalidArgumentException extends \InvalidArgumentException implements WooIndexException
{
    public static function invalidPriority(float $prio): self
    {
        return new self(sprintf('Priority must be between 0.0 and 1.0. Given: "%s"', $prio));
    }
}
