<?php

declare(strict_types=1);

namespace Admin\Api\Admin\Dossier;

use Shared\Domain\Publication\Dossier\AbstractDossier;

final readonly class DossierReferenceDto
{
    public function __construct(private string $id)
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public static function fromEntity(AbstractDossier $entity): self
    {
        return new self($entity->getId()->toRfc4122());
    }
}
