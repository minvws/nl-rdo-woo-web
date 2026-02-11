<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Department;

use Shared\Domain\Department\Department;
use Symfony\Component\Uid\Uuid;

final readonly class DepartmentReferenceDto
{
    public function __construct(
        public Uuid $id,
        public string $name,
    ) {
    }

    public static function fromEntity(Department $department): self
    {
        return new self(
            $department->getId(),
            $department->getName(),
        );
    }
}
