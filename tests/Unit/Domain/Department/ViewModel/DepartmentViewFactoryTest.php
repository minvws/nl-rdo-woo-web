<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Department\ViewModel;

use App\Domain\Department\ViewModel\DepartmentViewFactory;
use App\Entity\Department;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class DepartmentViewFactoryTest extends UnitTestCase
{
    private UrlGeneratorInterface&MockInterface $urlGenerator;

    protected function setUp(): void
    {
        $this->urlGenerator = \Mockery::mock(UrlGeneratorInterface::class);
    }

    public function testMake(): void
    {
        /** @var Department&MockInterface $department */
        $department = \Mockery::mock(Department::class);
        $department->shouldReceive('getName')->andReturn($expectedName = 'my name');
        $department->shouldReceive('getShortTag')->andReturn($expectedShortTag = 'my short tag');
        $department->shouldReceive('getSlug')->andReturn($expectedSlug = 'my-slug');

        $this->urlGenerator
            ->shouldReceive('generate')
            ->with('app_department_detail', ['slug' => $expectedSlug])
            ->andReturn($expectedUrl = 'my-url');

        $factory = new DepartmentViewFactory($this->urlGenerator);

        $actual = $factory->make($department);

        $this->assertSame($expectedName, $actual->name);
        $this->assertSame($expectedShortTag, $actual->tag);
        $this->assertSame($expectedUrl, $actual->url);
    }

    public function testMakeCollection(): void
    {
        /** @var Department&MockInterface $departmentOne */
        $departmentOne = \Mockery::mock(Department::class);
        $departmentOne->shouldReceive('getName')->andReturn('Department B');
        $departmentOne->shouldReceive('getShortTag')->andReturn($expectedShortB = 'my short tag b');
        $departmentOne->shouldReceive('getSlug')->andReturn($expectedSlugB = 'my-slug-b');

        /** @var Department&MockInterface $departmentTwo */
        $departmentTwo = \Mockery::mock(Department::class);
        $departmentTwo->shouldReceive('getName')->andReturn('Department C');
        $departmentTwo->shouldReceive('getShortTag')->andReturn($expectedShortC = 'my short tag c');
        $departmentTwo->shouldReceive('getSlug')->andReturn($expectedSlugC = 'my-slug-c');

        /** @var Department&MockInterface $departmentThree */
        $departmentThree = \Mockery::mock(Department::class);
        $departmentThree->shouldReceive('getName')->andReturn('Department A');
        $departmentThree->shouldReceive('getShortTag')->andReturn($expectedShortA = 'my short tag a');
        $departmentThree->shouldReceive('getSlug')->andReturn($expectedSlugA = 'my-slug-a');

        $this->urlGenerator
            ->shouldReceive('generate')
            ->with('app_department_detail', ['slug' => $expectedSlugA])
            ->andReturn($expectedUrlA = 'my-url-a');

        $this->urlGenerator
            ->shouldReceive('generate')
            ->with('app_department_detail', ['slug' => $expectedSlugB])
            ->andReturn($expectedUrlB = 'my-url-b');

        $this->urlGenerator
            ->shouldReceive('generate')
            ->with('app_department_detail', ['slug' => $expectedSlugC])
            ->andReturn($expectedUrlC = 'my-url-c');

        $factory = new DepartmentViewFactory($this->urlGenerator);

        $actual = $factory->makeCollection([$departmentOne, $departmentTwo, $departmentThree]);

        $this->assertCount(3, $actual);
        $this->assertSame('Department A', $actual[0]->name);
        $this->assertSame($expectedShortA, $actual[0]->tag);
        $this->assertSame($expectedUrlA, $actual[0]->url);

        $this->assertSame('Department B', $actual[1]->name);
        $this->assertSame($expectedShortB, $actual[1]->tag);
        $this->assertSame($expectedUrlB, $actual[1]->url);

        $this->assertSame('Department C', $actual[2]->name);
        $this->assertSame($expectedShortC, $actual[2]->tag);
        $this->assertSame($expectedUrlC, $actual[2]->url);
    }
}
