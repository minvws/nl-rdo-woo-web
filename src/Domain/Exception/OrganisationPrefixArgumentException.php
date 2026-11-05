<?php

declare(strict_types=1);

namespace Shared\Domain\Exception;

use InvalidArgumentException;

abstract class OrganisationPrefixArgumentException extends InvalidArgumentException
{
    abstract public function getMessageKey(): string;

    /**
     * @return array<string, int|string>
     */
    public function getParameters(): array
    {
        return [];
    }
}
