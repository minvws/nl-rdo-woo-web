<?php

declare(strict_types=1);

namespace App\Domain\Department\Exception;

use App\Entity\Department;

final class DepartmentAssetNotFound extends \RuntimeException implements DepartmentException
{
    public static function noLogoFound(Department $department, ?\Throwable $previous = null): self
    {
        return new self(sprintf(
            'Department with id "%s" does not have an logo file',
            $department->getId(),
        ), previous: $previous);
    }
}
