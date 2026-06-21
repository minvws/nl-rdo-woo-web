<?php

declare(strict_types=1);

namespace PublicationApi\Api\Department;

use PublicationApi\Api\Organisation\OrganisationResponseDto;
use Symfony\Component\Uid\Uuid;

final readonly class DepartmentDetailResponseDto
{
    /**
     * @param list<OrganisationResponseDto> $organisations
     */
    public function __construct(
        public Uuid $id,
        public string $name,
        public array $organisations,
    ) {
    }
}
