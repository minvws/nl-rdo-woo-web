<?php

declare(strict_types=1);

namespace PublicationApi\Api\Department;

use Shared\Domain\Department\Department;

use function array_map;
use function array_values;

class DepartmentMapper
{
    /**
     * @param array<array-key,Department> $departments
     *
     * @return array<array-key,DepartmentResponseDto>
     */
    public static function fromEntities(array $departments): array
    {
        return array_values(array_map(self::fromEntity(...), $departments));
    }

    public static function fromEntity(Department $department): DepartmentResponseDto
    {
        return new DepartmentResponseDto(
            $department->getId(),
            $department->getName(),
        );
    }
}
