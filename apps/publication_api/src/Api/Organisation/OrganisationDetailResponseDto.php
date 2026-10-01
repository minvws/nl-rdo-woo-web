<?php

declare(strict_types=1);

namespace PublicationApi\Api\Organisation;

use PublicationApi\Api\Department\DepartmentResponseDto;
use PublicationApi\Api\Subject\SubjectResponseDto;
use Symfony\Component\Uid\Uuid;

final readonly class OrganisationDetailResponseDto
{
    /**
     * @param list<DepartmentResponseDto> $departments
     * @param list<SubjectResponseDto> $subjects
     */
    public function __construct(
        public Uuid $id,
        public string $name,
        public string $prefix,
        public array $departments,
        public array $subjects,
    ) {
    }
}
