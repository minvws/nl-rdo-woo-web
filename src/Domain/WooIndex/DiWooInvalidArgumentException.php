<?php

declare(strict_types=1);

namespace App\Domain\WooIndex;

final class DiWooInvalidArgumentException extends \InvalidArgumentException implements DiWooException
{
    public static function invalidPriority(float $prio): self
    {
        return new self(sprintf('Priority must be between 0.0 and 1.0. Given: "%s"', $prio));
    }

    public static function invalidTreshold(int $treshold): self
    {
        return new self(sprintf('Treshold should not be less than 1. Given: %s', $treshold));
    }
}
