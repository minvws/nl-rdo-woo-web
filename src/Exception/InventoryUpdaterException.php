<?php

declare(strict_types=1);

namespace App\Exception;

class InventoryUpdaterException extends \RuntimeException
{
    public static function forStateMismatch(): self
    {
        return new self('State mismatch between database and changeset');
    }
}
