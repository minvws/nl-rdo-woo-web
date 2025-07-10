<?php

declare(strict_types=1);

namespace App\Domain\Department;

use Symfony\Component\Uid\Uuid;

readonly class UpdateDepartmentCommand
{
    public function __construct(
        private Uuid $uuid,
    ) {
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public static function forDepartment(Department $department): self
    {
        return new self($department->getId());
    }
}
