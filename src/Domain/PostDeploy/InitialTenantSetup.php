<?php

declare(strict_types=1);

namespace Shared\Domain\PostDeploy;

use Shared\Domain\Department\Department;
use Shared\Domain\Department\DepartmentRepository;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\ValueObject\OrganisationPrefix;

readonly class InitialTenantSetup
{
    public function __construct(
        private OrganisationRepository $organisationRepository,
        private DepartmentRepository $departmentRepository,
    ) {
    }

    public function isNewEnvironment(): bool
    {
        return ! $this->organisationRepository->hasAny();
    }

    public function exec(): false|Organisation
    {
        if ($this->organisationRepository->hasAny()) {
            return false;
        }

        return $this->createInitialOrganisation($this->createInitialDeparment());
    }

    private function createInitialDeparment(): Department
    {
        $department = new Department();
        $department->setName('Placeholder Department');
        $department->setSlug('placeholder_department');

        $this->departmentRepository->save($department);

        return $department;
    }

    private function createInitialOrganisation(Department $department): Organisation
    {
        $organisation = new Organisation();
        $organisation->addDepartment($department);
        $organisation->setName('Placeholder Organisation');
        $organisation->setPrefix(OrganisationPrefix::create('placeholder-organisation'));

        $this->organisationRepository->save($organisation, flush: true);

        return $organisation;
    }
}
