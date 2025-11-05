<?php

declare(strict_types=1);

namespace App\Api\Publication\V1\Department;

use App\Domain\Department\Department;

class DepartmentMapper
{
    /**
     * @param array<array-key,Department> $departments
     *
     * @return array<array-key,DepartmentDto>
     */
    public static function fromEntities(array $departments): array
    {
        return array_map(fn (Department $department): DepartmentDto => self::fromEntity($department), $departments);
    }

    public static function fromEntity(Department $department): DepartmentDto
    {
        return new DepartmentDto(
            $department->getId(),
            $department->getName(),
        );
    }
}
