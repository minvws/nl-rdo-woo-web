<?php

declare(strict_types=1);

namespace Shared\Exception\Import;

use RuntimeException;

abstract class ImportException extends RuntimeException
{
    abstract public function getMessageKey(): string;

    /**
     * @return array<string, string>
     */
    public function getMessageData(): array
    {
        return [];
    }
}
