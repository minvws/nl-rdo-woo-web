<?php

declare(strict_types=1);

namespace App\Domain\Department;

use App\Domain\Department\Department as DepartmentEntity;
use App\Domain\Department\ViewModel\Department;
use App\Domain\Department\ViewModel\DepartmentViewFactory;
use App\Service\Security\Authorization\AuthorizationMatrix;
use App\Service\Security\Authorization\AuthorizationMatrixFilter;

readonly class DepartmentService
{
    public function __construct(
        private DepartmentRepository $repository,
        private DepartmentViewFactory $departmentViewFactory,
        private AuthorizationMatrix $authorizationMatrix,
    ) {
    }

    /**
     * @return list<Department>
     */
    public function getPublicDepartments(): array
    {
        return $this->departmentViewFactory->makeCollection($this->repository->getAllPublicDepartments());
    }

    public function userCanEditLandingpage(DepartmentEntity $department): bool
    {
        if (! $this->authorizationMatrix->isAuthorized('department_landing_page', 'update')) {
            return false;
        }

        if ($this->authorizationMatrix->hasFilter(AuthorizationMatrixFilter::ORGANISATION_ONLY)) {
            return $this->authorizationMatrix->getActiveOrganisation()->hasDepartment($department);
        }

        return true;
    }
}
