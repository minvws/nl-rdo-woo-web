<?php

declare(strict_types=1);

namespace App\Message;

use App\Entity\Department;
use Symfony\Component\Uid\Uuid;

class UpdateDepartmentMessage
{
    public function __construct(
        private readonly Uuid $uuid,
    ) {
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public static function forDepartment(Department $department): self
    {
        return new self($department->getId());
    }
}
