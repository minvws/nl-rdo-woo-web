<?php

declare(strict_types=1);

namespace PublicationApi\Api\Department;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Uid\Uuid;

final readonly class DepartmentResponseDto
{
    public function __construct(
        #[ApiProperty(description: 'The unique identifier of the department.')]
        public Uuid $id,
        #[ApiProperty(description: 'The name of the department.')]
        public string $name,
    ) {
    }
}
