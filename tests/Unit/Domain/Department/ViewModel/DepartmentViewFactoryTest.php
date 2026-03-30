<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Department\ViewModel;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Department\Department;
use Shared\Domain\Department\ViewModel\DepartmentViewFactory;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class DepartmentViewFactoryTest extends UnitTestCase
{
    private UrlGeneratorInterface&MockInterface $urlGenerator;

    protected function setUp(): void
    {
        $this->urlGenerator = Mockery::mock(UrlGeneratorInterface::class);
    }

    public function testMake(): void
    {
        $department = Mockery::mock(Department::class);
        $department->expects('getName')->andReturn($expectedName = 'my name');
        $department->expects('getShortTag')->andReturn($expectedShortTag = 'my short tag');
        $department->expects('getSlug')->andReturn($expectedSlug = 'my-slug');

        $this->urlGenerator
            ->expects('generate')
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
        $departmentOne = Mockery::mock(Department::class);
        $departmentOne->expects('getName')->andReturn('Department B');
        $departmentOne->expects('getShortTag')->andReturn($expectedShortB = 'my short tag b');
        $departmentOne->expects('getSlug')->andReturn($expectedSlugB = 'my-slug-b');

        $departmentTwo = Mockery::mock(Department::class);
        $departmentTwo->expects('getName')->andReturn('Department C');
        $departmentTwo->expects('getShortTag')->andReturn($expectedShortC = 'my short tag c');
        $departmentTwo->expects('getSlug')->andReturn($expectedSlugC = 'my-slug-c');

        $departmentThree = Mockery::mock(Department::class);
        $departmentThree->expects('getName')->andReturn('Department A');
        $departmentThree->expects('getShortTag')->andReturn($expectedShortA = 'my short tag a');
        $departmentThree->expects('getSlug')->andReturn($expectedSlugA = 'my-slug-a');

        $this->urlGenerator
            ->expects('generate')
            ->with('app_department_detail', ['slug' => $expectedSlugA])
            ->andReturn($expectedUrlA = 'my-url-a');

        $this->urlGenerator
            ->expects('generate')
            ->with('app_department_detail', ['slug' => $expectedSlugB])
            ->andReturn($expectedUrlB = 'my-url-b');

        $this->urlGenerator
            ->expects('generate')
            ->with('app_department_detail', ['slug' => $expectedSlugC])
            ->andReturn($expectedUrlC = 'my-url-c');

        $factory = new DepartmentViewFactory($this->urlGenerator);

        $actual = $factory->makeCollection([$departmentOne, $departmentTwo, $departmentThree]);

        $this->assertCount(3, $actual);
        $this->assertSame('Department B', $actual[0]->name);
        $this->assertSame($expectedShortB, $actual[0]->tag);
        $this->assertSame($expectedUrlB, $actual[0]->url);

        $this->assertSame('Department C', $actual[1]->name);
        $this->assertSame($expectedShortC, $actual[1]->tag);
        $this->assertSame($expectedUrlC, $actual[1]->url);

        $this->assertSame('Department A', $actual[2]->name);
        $this->assertSame($expectedShortA, $actual[2]->tag);
        $this->assertSame($expectedUrlA, $actual[2]->url);
    }
}
