<?php

declare(strict_types=1);

namespace Shared\Api\Publication\V1\Organisation;

use Shared\Domain\Organisation\Organisation;
use Symfony\Component\Uid\Uuid;

final readonly class OrganisationReferenceDto
{
    public function __construct(
        private Uuid $id,
        private string $name,
    ) {
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public static function fromEntity(Organisation $entity): self
    {
        return new self($entity->getId(), $entity->getName());
    }
}
