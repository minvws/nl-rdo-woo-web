<?php

declare(strict_types=1);

namespace PublicationApi\Api\Organisation;

use PublicationApi\Api\Department\DepartmentResponseDto;
use PublicationApi\Api\Prefix\PrefixResponseDto;
use PublicationApi\Api\Subject\SubjectResponse;
use Symfony\Component\Uid\Uuid;

final readonly class OrganisationDetailResponseDto
{
    /**
     * @param list<DepartmentResponseDto> $departments
     * @param list<SubjectResponse> $subjects
     * @param list<PrefixResponseDto> $prefixes
     */
    public function __construct(
        public Uuid $id,
        public string $name,
        public array $departments,
        public array $subjects,
        public array $prefixes,
    ) {
    }
}
