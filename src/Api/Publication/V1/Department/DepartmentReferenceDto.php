<?php

declare(strict_types=1);

namespace Shared\Api\Publication\V1\Department;

use Shared\Domain\Department\Department;
use Symfony\Component\Uid\Uuid;

final readonly class DepartmentReferenceDto
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

    public static function fromEntity(Department $department): self
    {
        return new self(
            $department->getId(),
            $department->getName(),
        );
    }
}
