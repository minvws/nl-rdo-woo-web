<?php

declare(strict_types=1);

namespace App\Api\Publication\V1\Organisation;

use App\Domain\Organisation\Organisation;
use Symfony\Component\Uid\Uuid;

final readonly class OrganisationReferenceDto
{
    public function __construct(
        private Uuid $id,
    ) {
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public static function fromEntity(Organisation $entity): self
    {
        return new self($entity->getId());
    }
}
