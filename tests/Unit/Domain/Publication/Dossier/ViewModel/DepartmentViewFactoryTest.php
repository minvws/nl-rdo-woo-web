<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\ViewModel;

use App\Domain\Publication\Dossier\ViewModel\DepartmentViewFactory;
use App\Entity\Department;
use App\Tests\Unit\UnitTestCase;
use Doctrine\Common\Collections\ArrayCollection;

final class DepartmentViewFactoryTest extends UnitTestCase
{
    public function testMake(): void
    {
        $department = \Mockery::mock(Department::class);
        $department->shouldReceive('getName')->andReturn($expectedName = 'my name');

        $result = (new DepartmentViewFactory())->make($department);

        $this->assertSame($expectedName, $result->name);
    }

    public function testMakeCollection(): void
    {
        $departmentOne = \Mockery::mock(Department::class);
        $departmentOne->shouldReceive('getName')->andReturn($expectedNameOne = 'my name one');

        $departmentTwo = \Mockery::mock(Department::class);
        $departmentTwo->shouldReceive('getName')->andReturn($expectedNameTwo = 'my name two');

        /** @var ArrayCollection<array-key,Department> $departments */
        $departments = new ArrayCollection([$departmentOne, $departmentTwo]);

        $results = (new DepartmentViewFactory())->makeCollection($departments);

        $this->assertCount(2, $results);
        $this->assertSame($expectedNameOne, $results[0]?->name);
        $this->assertSame($expectedNameTwo, $results[1]?->name);
    }
}
