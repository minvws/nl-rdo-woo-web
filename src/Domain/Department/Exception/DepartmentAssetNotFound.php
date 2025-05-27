<?php

declare(strict_types=1);

namespace App\Domain\Department\Exception;

use App\Entity\Department;

final class DepartmentAssetNotFound extends \RuntimeException implements DepartmentException
{
    public static function create(Department $department, string $file, ?\Throwable $previous = null): self
    {
        return new self(sprintf(
            'Department with id "%s" does not have an asset file named "%s"',
            $department->getId(),
            $file,
        ), previous: $previous);
    }
}
