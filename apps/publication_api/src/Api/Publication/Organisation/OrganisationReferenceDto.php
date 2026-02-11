<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Organisation;

use Shared\Domain\Organisation\Organisation;
use Symfony\Component\Uid\Uuid;

final readonly class OrganisationReferenceDto
{
    public function __construct(
        public Uuid $id,
        public string $name,
    ) {
    }

    public static function fromEntity(Organisation $entity): self
    {
        return new self($entity->getId(), $entity->getName());
    }
}
