<?php

declare(strict_types=1);

namespace App\Api\Admin\Dossier;

use ApiPlatform\Metadata\ApiProperty;
use App\Domain\Publication\Dossier\AbstractDossier;

final readonly class DossierReferenceDto
{
    public function __construct(
        #[ApiProperty(writable: false, identifier: true, genId: false)]
        private string $id,
    ) {
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
