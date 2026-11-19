<?php

declare(strict_types=1);

namespace PublicationApi\Domain\Exception;

use Override;
use Shared\Domain\Exception\ProvidesDiagnosticContext;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class EntityNotFoundException extends NotFoundHttpException implements ProvidesDiagnosticContext
{
    private function __construct(
        public readonly string $entityName,
        public readonly string $id,
    ) {
        parent::__construct('Entity not found');
    }

    public static function for(string $entityName, string $id): self
    {
        return new self($entityName, $id);
    }

    #[Override]
    public function getDiagnosticContext(): array
    {
        return [
            'entityName' => $this->entityName,
            'id' => $this->id,
        ];
    }
}
