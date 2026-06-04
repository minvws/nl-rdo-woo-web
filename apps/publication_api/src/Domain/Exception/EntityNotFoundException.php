<?php

declare(strict_types=1);

namespace PublicationApi\Domain\Exception;

use RuntimeException;
use Stringable;

final class EntityNotFoundException extends RuntimeException
{
    private function __construct(
        public readonly string $entityName,
        public readonly string|Stringable $id,
    ) {
        parent::__construct('Entity not found');
    }

    public static function for(string $entityName, string|Stringable $id): self
    {
        return new self($entityName, $id);
    }
}
