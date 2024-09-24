<?php

declare(strict_types=1);

namespace App\Domain\Department;

use App\Domain\Department\ViewModel\Department;
use App\Domain\Department\ViewModel\DepartmentViewFactory;
use App\Repository\DepartmentRepository;

final readonly class DepartmentService
{
    public function __construct(
        private DepartmentRepository $repository,
        private DepartmentViewFactory $departmentViewFactory,
    ) {
    }

    /**
     * @return list<Department>
     */
    public function getPublicDepartments(): array
    {
        return $this->departmentViewFactory->makeCollection($this->repository->getAllPublicDepartments());
    }
}
