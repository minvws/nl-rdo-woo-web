<?php

declare(strict_types=1);

namespace PublicationApi\Api\Department;

use PublicationApi\Api\Organisation\OrganisationMapper;
use Shared\Domain\Department\Department;

use function array_map;
use function array_values;

readonly class DepartmentMapper
{
    public function __construct(
        private OrganisationMapper $organisationMapper,
    ) {
    }

    /**
     * @param array<array-key,Department> $departments
     *
     * @return list<DepartmentDetailResponseDto>
     */
    public function fromEntitiesWithDetail(array $departments): array
    {
        return array_values(array_map($this->fromEntityWithDetail(...), $departments));
    }

    public function fromEntityWithDetail(Department $department): DepartmentDetailResponseDto
    {
        return new DepartmentDetailResponseDto(
            $department->getId(),
            $department->getName(),
            $this->organisationMapper::fromEntities($department->getOrganisations()->toArray()),
        );
    }

    /**
     * @param array<array-key,Department> $departments
     *
     * @return list<DepartmentResponseDto>
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
