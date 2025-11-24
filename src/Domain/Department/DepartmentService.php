<?php

declare(strict_types=1);

namespace Shared\Domain\Department;

use Shared\Domain\Department\Department as DepartmentEntity;
use Shared\Domain\Department\ViewModel\Department;
use Shared\Domain\Department\ViewModel\DepartmentViewFactory;
use Shared\Service\Security\Authorization\AuthorizationMatrix;
use Shared\Service\Security\Authorization\AuthorizationMatrixFilter;

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
