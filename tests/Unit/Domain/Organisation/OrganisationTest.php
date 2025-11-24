<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Organisation;

use Doctrine\Common\Collections\Collection;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DocumentPrefix;
use Shared\Service\Security\User;
use Shared\Tests\Unit\UnitTestCase;

class OrganisationTest extends UnitTestCase
{
    public function testSetAndGetName(): void
    {
        $organisation = new Organisation();
        $organisation->setName($name = 'Foo Bar');

        self::assertEquals($name, $organisation->getName());
    }

    public function testAddAndRemoveDepartment(): void
    {
        $department = \Mockery::mock(Department::class);

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

        $user = \Mockery::mock(User::class);
        $user->expects('setOrganisation')->with($organisation);

        $organisation->addUser($user);

        self::assertEquals([$user], $organisation->getUsers()->toArray());

        $organisation->removeUser($user);

        self::assertEquals([], $organisation->getUsers()->toArray());
    }

    public function testGetDocumentPrefixesSkipsArchived(): void
    {
        $organisation = new Organisation();

        $activePrefix = \Mockery::mock(DocumentPrefix::class);
        $activePrefix->expects('setOrganisation')->with($organisation);
        $activePrefix->shouldReceive('isArchived')->andReturn(false);
        $activePrefix->shouldReceive('getPrefix')->andReturn('foo');

        $archivedPrefix = \Mockery::mock(DocumentPrefix::class);
        $archivedPrefix->expects('setOrganisation')->with($organisation);
        $archivedPrefix->shouldReceive('isArchived')->andReturn(true);
        $archivedPrefix->shouldReceive('getPrefix')->andReturn('bar');

        $organisation->addDocumentPrefix($activePrefix);
        $organisation->addDocumentPrefix($archivedPrefix);

        self::assertEquals([$activePrefix], $organisation->getDocumentPrefixes()->toArray());
        self::assertEquals(['foo'], $organisation->getPrefixesAsArray());
    }

    public function testRemoveDocumentPrefix(): void
    {
        $organisation = new Organisation();

        $prefix = \Mockery::mock(DocumentPrefix::class);
        $prefix->expects('setOrganisation')->with($organisation);

        $organisation->addDocumentPrefix($prefix);

        $prefix->expects('archive');

        $organisation->removeDocumentPrefix($prefix);
    }

    public function testSetAndGetInquiries(): void
    {
        $inquiries = \Mockery::mock(Collection::class);

        $organisation = new Organisation();
        $organisation->setInquiries($inquiries);

        self::assertEquals($inquiries, $organisation->getInquiries());
    }

    public function testSetAndGetDossiers(): void
    {
        $dossiers = \Mockery::mock(Collection::class);

        $organisation = new Organisation();
        $organisation->setDossiers($dossiers);

        self::assertEquals($dossiers, $organisation->getDossiers());
    }
}
