<?php

declare(strict_types=1);

namespace Shared\Api\Publication\V1\Department;

use Shared\Domain\Department\Department;

class DepartmentMapper
{
    /**
     * @param array<array-key,Department> $departments
     *
     * @return array<array-key,DepartmentDto>
     */
    public static function fromEntities(array $departments): array
    {
        return array_map(self::fromEntity(...), $departments);
    }

    public static function fromEntity(Department $department): DepartmentDto
    {
        return new DepartmentDto(
            $department->getId(),
            $department->getName(),
        );
    }
}
