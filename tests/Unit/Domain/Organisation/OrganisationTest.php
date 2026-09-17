<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Organisation;

use Doctrine\Common\Collections\Collection;
use Mockery;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Service\Security\User;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\OrganisationPrefix;

class OrganisationTest extends UnitTestCase
{
    public function testSetAndGetName(): void
    {
        $organisation = new Organisation();
        $organisation->setName($name = 'Foo Bar');

        self::assertEquals($name, $organisation->getName());
    }

    public function testSetAndGetPrefix(): void
    {
        $organisation = new Organisation();
        $organisation->setPrefix(OrganisationPrefix::create('abc-12'));

        self::assertSame('ABC-12', $organisation->getPrefix()->toString());
    }

    public function testAddAndRemoveDepartment(): void
    {
        $department = Mockery::mock(Department::class);

        $organisation = new Organisation();
        $organisation->addDepartment($department);

        self::assertEquals([$department], $organisation->getDepartments()->toArray());
        self::assertTrue($organisation->hasDepartment($department));

        $organisation->removeDepartment($department);

        self::assertEquals([], $organisation->getDepartments()->toArray());
        self::assertFalse($organisation->hasDepartment($department));
    }

    public function testAddAndRemoveUser(): void
    {
        $organisation = new Organisation();

        $user = Mockery::mock(User::class);
        $user->expects('setOrganisation')->with($organisation);

        $organisation->addUser($user);

        self::assertEquals([$user], $organisation->getUsers()->toArray());

        $organisation->removeUser($user);

        self::assertEquals([], $organisation->getUsers()->toArray());
    }

    public function testSetAndGetInquiries(): void
    {
        $inquiries = Mockery::mock(Collection::class);

        $organisation = new Organisation();
        $organisation->setInquiries($inquiries);

        self::assertEquals($inquiries, $organisation->getInquiries());
    }

    public function testSetAndGetDossiers(): void
    {
        $dossiers = Mockery::mock(Collection::class);

        $organisation = new Organisation();
        $organisation->setDossiers($dossiers);

        self::assertEquals($dossiers, $organisation->getDossiers());
    }
}
