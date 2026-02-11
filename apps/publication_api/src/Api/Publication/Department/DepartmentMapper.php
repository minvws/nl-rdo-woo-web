<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Department;

use Shared\Domain\Department\Department;

use function array_map;
use function array_values;

class DepartmentMapper
{
    /**
     * @param array<array-key,Department> $departments
     *
     * @return array<array-key,DepartmentDto>
     */
    public static function fromEntities(array $departments): array
    {
        return array_values(array_map(self::fromEntity(...), $departments));
    }

    public static function fromEntity(Department $department): DepartmentDto
    {
        return new DepartmentDto(
            $department->getId(),
            $department->getName(),
        );
    }
}
