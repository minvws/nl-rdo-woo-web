<?php

declare(strict_types=1);

namespace Shared\Domain\Department\Exception;

use RuntimeException;
use Shared\Domain\Department\Department;
use Throwable;

use function sprintf;

final class DepartmentAssetNotFound extends RuntimeException implements DepartmentException
{
    public static function noLogoFound(Department $department, ?Throwable $previous = null): self
    {
        return new self(sprintf(
            'Department with id "%s" does not have an logo file',
            $department->getId(),
        ), previous: $previous);
    }
}
