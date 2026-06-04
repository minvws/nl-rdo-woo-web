<?php

declare(strict_types=1);

namespace PublicationApi\Api\Department;

use Symfony\Component\Uid\Uuid;

final readonly class DepartmentRequestDto
{
    public function __construct(
        public Uuid $id,
        public string $name,
    ) {
    }
}
