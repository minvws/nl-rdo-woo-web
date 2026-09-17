<?php

declare(strict_types=1);

namespace Admin\Form\Organisation;

use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\ValueObject\OrganisationPrefix;
use Webmozart\Assert\Assert;

use function array_values;
use function in_array;

final class OrganisationFormMapper
{
    public function create(OrganisationFormData $data): Organisation
    {
        $organisation = new Organisation();
        $this->apply($data, $organisation);

        return $organisation;
    }

    public function apply(OrganisationFormData $data, Organisation $organisation): void
    {
        $prefix = $data->prefix;
        Assert::isInstanceOf($prefix, OrganisationPrefix::class);
        Assert::allIsInstanceOf($data->departments, Department::class);

        $organisation
            ->setName($data->name)
            ->setPrefix($prefix);

        /** @var list<Department> $currentDepartments */
        $currentDepartments = array_values($organisation->getDepartments()->toArray());
        foreach ($currentDepartments as $department) {
            if (! in_array($department, $data->departments, true)) {
                $organisation->removeDepartment($department);
            }
        }

        foreach ($data->departments as $department) {
            $organisation->addDepartment($department);
        }
    }
}
