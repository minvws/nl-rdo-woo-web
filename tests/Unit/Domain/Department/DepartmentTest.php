<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Department;

use App\Domain\Department\Department;
use App\Domain\Organisation\Organisation;
use App\Domain\Publication\FileInfo;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class DepartmentTest extends MockeryTestCase
{
    public function testSetAndGetLandingPageTitle(): void
    {
        $department = new Department();
        $department->setLandingPageTitle($title = 'foo');

        self::assertEquals($title, $department->getLandingPageTitle());
    }

    public function testSetAndGetLandingPageDescription(): void
    {
        $department = new Department();
        $department->setLandingPageDescription($description = 'foo');

        self::assertEquals($description, $department->getLandingPageDescription());
    }

    public function testSetAndGetFeedbackContent(): void
    {
        $department = new Department();
        $department->setFeedbackContent($content = 'foo');

        self::assertEquals($content, $department->getFeedbackContent());
    }

    public function testSetAndGetFileInfo(): void
    {
        $department = new Department();

        $fileInfo = new FileInfo();
        $department->setFileInfo($fileInfo);

        self::assertSame($fileInfo, $department->getFileInfo());
    }

    public function testSetAndGetShortTag(): void
    {
        $department = new Department();
        $department->setShortTag($tag = 'foo');

        self::assertEquals($tag, $department->getShortTag());
    }

    public function testGetNameAndShort(): void
    {
        $department = new Department();
        $department->setShortTag('foo');
        $department->setName('bar');

        self::assertEquals('bar (foo)', $department->nameAndShort());
    }

    public function testAddAndRemoveOrganisation(): void
    {
        $department = new Department();

        $organisation = \Mockery::mock(Organisation::class);
        $organisation->expects('addDepartment')->with($department);

        $department->addOrganisation($organisation);

        self::assertEquals([$organisation], $department->getOrganisations()->toArray());

        $department->removeOrganisation($organisation);

        self::assertEquals([], $department->getOrganisations()->toArray());
    }
}
