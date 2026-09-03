<?php

declare(strict_types=1);

namespace PublicationApi\Domain\Exception;

use Override;
use RuntimeException;
use Shared\Domain\Exception\ProvidesDiagnosticContext;
use Stringable;

final class EntityNotFoundException extends RuntimeException implements ProvidesDiagnosticContext
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

    #[Override]
    public function getDiagnosticContext(): array
    {
        return [
            'entityName' => $this->entityName,
            'id' => $this->id instanceof Stringable
                ? $this->id->__toString()
                : $this->id,
        ];
    }
}
