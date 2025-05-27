<?php

declare(strict_types=1);

namespace App\Domain\Department;

use App\Domain\Department\ViewModel\Department;
use App\Domain\Department\ViewModel\DepartmentViewFactory;
use App\Entity\Department as DepartmentEntity;
use App\Repository\DepartmentRepository;
use App\Service\Security\Authorization\AuthorizationMatrix;
use App\Service\Security\Authorization\AuthorizationMatrixFilter;
use Twig\Environment;

final readonly class DepartmentService
{
    public function __construct(
        private DepartmentRepository $repository,
        private DepartmentViewFactory $departmentViewFactory,
        private Environment $twig,
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

    public function getTemplate(DepartmentEntity $department): string
    {
        if (! $this->hasCustomTemplate($department)) {
            return 'public/department/details_default.html.twig';
        }

        return $this->getCustomTemplatePath($department);
    }

    private function hasCustomTemplate(DepartmentEntity $department): bool
    {
        return $this->twig->getLoader()->exists($this->getCustomTemplatePath($department));
    }

    private function getCustomTemplatePath(DepartmentEntity $department): string
    {
        return 'public/department/custom/' . $department->getSlug() . '.html.twig';
    }

    public function userCanEditLandingpage(DepartmentEntity $department): bool
    {
        if ($this->hasCustomTemplate($department)) {
            return false;
        }

        if (! $this->authorizationMatrix->isAuthorized('department_landing_page', 'update')) {
            return false;
        }

        if ($this->authorizationMatrix->hasFilter(AuthorizationMatrixFilter::ORGANISATION_ONLY)) {
            return $this->authorizationMatrix->getActiveOrganisation()->hasDepartment($department);
        }

        return true;
    }
}
