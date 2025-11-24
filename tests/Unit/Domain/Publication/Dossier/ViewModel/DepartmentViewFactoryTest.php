<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\ViewModel;

use Doctrine\Common\Collections\ArrayCollection;
use Shared\Domain\Department\Department;
use Shared\Domain\Publication\Dossier\ViewModel\DepartmentViewFactory;
use Shared\Tests\Unit\UnitTestCase;

final class DepartmentViewFactoryTest extends UnitTestCase
{
    public function testMake(): void
    {
        $department = \Mockery::mock(Department::class);
        $department->shouldReceive('getName')->andReturn($expectedName = 'my name');
        $department
            ->shouldReceive('getFeedbackContent')
            ->andReturn($feedbackContent = 'some feedback content');
        $department
            ->shouldReceive('getResponsibilityContent')
            ->andReturn($responsibilityContent = 'some responsibility content');

        $result = (new DepartmentViewFactory())->make($department);

        $this->assertSame($expectedName, $result->name);
        $this->assertSame($feedbackContent, $result->feedbackContent);
        $this->assertSame($responsibilityContent, $result->responsibilityContent);
    }

    public function testMakeCollection(): void
    {
        $departmentOne = \Mockery::mock(Department::class);
        $departmentOne->shouldReceive('getName')->andReturn($expectedNameOne = 'my name one');
        $departmentOne
            ->shouldReceive('getFeedbackContent')
            ->andReturn($feedbackContentOne = 'some feedback content');
        $departmentOne
            ->shouldReceive('getResponsibilityContent')
            ->andReturn($responsibilityContentOne = 'some responsibility content');

        $departmentTwo = \Mockery::mock(Department::class);
        $departmentTwo->shouldReceive('getName')->andReturn($expectedNameTwo = 'my name two');
        $departmentTwo
            ->shouldReceive('getFeedbackContent')
            ->andReturn($feedbackContentTwo = null);
        $departmentTwo
            ->shouldReceive('getResponsibilityContent')
            ->andReturn($responsibilityContentTwo = null);

        /** @var ArrayCollection<array-key,Department> $departments */
        $departments = new ArrayCollection([$departmentOne, $departmentTwo]);

        $results = (new DepartmentViewFactory())->makeCollection($departments);

        $this->assertCount(2, $results);
        $this->assertSame($expectedNameOne, $results[0]?->name);
        $this->assertSame($feedbackContentOne, $results[0]->feedbackContent);
        $this->assertSame($responsibilityContentOne, $results[0]->responsibilityContent);
        $this->assertSame($expectedNameTwo, $results[1]?->name);
        $this->assertSame($feedbackContentTwo, $results[1]->feedbackContent);
        $this->assertSame($responsibilityContentTwo, $results[1]->responsibilityContent);
    }
}
