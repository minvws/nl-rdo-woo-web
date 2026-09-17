<?php

declare(strict_types=1);

namespace Admin\Tests\Unit\Form\Organisation;

use Admin\Form\Organisation\OrganisationFormData;
use Admin\Form\Organisation\OrganisationFormMapper;
use Mockery;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\OrganisationPrefix;

use function array_values;

final class OrganisationFormMapperTest extends UnitTestCase
{
    public function testCreateBuildsACompleteOrganisation(): void
    {
        $firstDepartment = Mockery::mock(Department::class);
        $secondDepartment = Mockery::mock(Department::class);
        $data = new OrganisationFormData(
            name: 'Organisation name',
            prefix: OrganisationPrefix::create('ABC-12'),
            departments: [$firstDepartment, $secondDepartment],
        );

        $organisation = new OrganisationFormMapper()->create($data);

        self::assertSame('Organisation name', $organisation->getName());
        self::assertSame('ABC-12', $organisation->getPrefix()->toString());
        self::assertSame([$firstDepartment, $secondDepartment], $organisation->getDepartments()->toArray());
    }

    public function testApplyUpdatesAnExistingOrganisationAndReconcilesDepartments(): void
    {
        $oldDepartment = Mockery::mock(Department::class);
        $keptDepartment = Mockery::mock(Department::class);
        $newDepartment = Mockery::mock(Department::class);

        $organisation = new Organisation();
        $organisation
            ->setName('Old name')
            ->setPrefix(OrganisationPrefix::create('OLD-01'))
            ->addDepartment($oldDepartment)
            ->addDepartment($keptDepartment);

        $data = new OrganisationFormData(
            name: 'New name',
            prefix: OrganisationPrefix::create('NEW-02'),
            departments: [$keptDepartment, $newDepartment],
            organisationId: $organisation->getId(),
        );

        new OrganisationFormMapper()->apply($data, $organisation);

        self::assertSame('New name', $organisation->getName());
        self::assertSame('NEW-02', $organisation->getPrefix()->toString());
        self::assertSame([$keptDepartment, $newDepartment], array_values($organisation->getDepartments()->toArray()));
    }
}
