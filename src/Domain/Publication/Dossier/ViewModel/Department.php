<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\ViewModel;

use App\Enum\Department as DepartmentEnum;

readonly class Department
{
    public function __construct(
        public string $name,
    ) {
    }

    public function isDepartment(DepartmentEnum $enum): bool
    {
        return $enum->equals($this->name);
    }
}
