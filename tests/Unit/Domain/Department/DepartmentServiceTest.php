<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Department;

use App\Domain\Department\Department as DepartmentEntity;
use App\Domain\Department\DepartmentRepository;
use App\Domain\Department\DepartmentService;
use App\Domain\Department\ViewModel\Department;
use App\Domain\Department\ViewModel\DepartmentViewFactory;
use App\Service\Security\Authorization\AuthorizationMatrix;
use App\Service\Security\Authorization\AuthorizationMatrixFilter;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Twig\Environment;

final class DepartmentServiceTest extends UnitTestCase
{
    private DepartmentRepository&MockInterface $repository;
    private DepartmentViewFactory&MockInterface $departmentViewFactory;
    private Environment&MockInterface $twig;
    private AuthorizationMatrix&MockInterface $authorizationMatrix;
    private DepartmentService $departmentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = \Mockery::mock(DepartmentRepository::class);
        $this->departmentViewFactory = \Mockery::mock(DepartmentViewFactory::class);
        $this->twig = \Mockery::mock(Environment::class);
        $this->authorizationMatrix = \Mockery::mock(AuthorizationMatrix::class);

        $this->departmentService = new DepartmentService(
            $this->repository,
            $this->departmentViewFactory,
            $this->twig,
            $this->authorizationMatrix,
        );
    }

    public function testGetPublicDepartments(): void
    {
        $this->repository
            ->shouldReceive('getAllPublicDepartments')
            ->andReturn($departmentEntities = [
                \Mockery::mock(DepartmentEntity::class),
                \Mockery::mock(DepartmentEntity::class),
            ]);

        $this->departmentViewFactory
            ->shouldReceive('makeCollection')
            ->with($departmentEntities)
            ->andReturn($expected = [
                \Mockery::mock(Department::class),
                \Mockery::mock(Department::class),
            ]);

        self::assertSame(
            $expected,
            $this->departmentService->getPublicDepartments(),
        );
    }

    public function testGetTemplateWithCustomPath(): void
    {
        $department = \Mockery::mock(DepartmentEntity::class);
        $department->shouldReceive('getSlug')->andReturn('foo');

        $expectedTemplatePath = 'public/department/custom/foo.html.twig';

        $this->twig->expects('getLoader->exists')->with($expectedTemplatePath)->andReturnTrue();

        self::assertEquals(
            $expectedTemplatePath,
            $this->departmentService->getTemplate($department),
        );
    }

    public function testGetTemplateWithDefaultPath(): void
    {
        $department = \Mockery::mock(DepartmentEntity::class);
        $department->shouldReceive('getSlug')->andReturn('foo');

        $customTemplatePath = 'public/department/custom/foo.html.twig';

        $this->twig->expects('getLoader->exists')->with($customTemplatePath)->andReturnFalse();

        self::assertEquals(
            'public/department/details_default.html.twig',
            $this->departmentService->getTemplate($department),
        );
    }

    public function testUserCanEditLandingpageReturnsFalseForDepartmentWithCustomTemplate(): void
    {
        $department = \Mockery::mock(DepartmentEntity::class);
        $department->shouldReceive('getSlug')->andReturn('foo');

        $expectedTemplatePath = 'public/department/custom/foo.html.twig';
        $this->twig->expects('getLoader->exists')->with($expectedTemplatePath)->andReturnTrue();

        self::assertFalse(
            $this->departmentService->userCanEditLandingpage($department),
        );
    }

    public function testUserCanEditLandingpageReturnsFalseWithoutMatrixPermission(): void
    {
        $department = \Mockery::mock(DepartmentEntity::class);
        $department->shouldReceive('getSlug')->andReturn('foo');

        $expectedTemplatePath = 'public/department/custom/foo.html.twig';
        $this->twig->expects('getLoader->exists')->with($expectedTemplatePath)->andReturnFalse();

        $this->authorizationMatrix
            ->expects('isAuthorized')
            ->with('department_landing_page', 'update')
            ->andReturnFalse();

        self::assertFalse(
            $this->departmentService->userCanEditLandingpage($department),
        );
    }

    public function testUserCanEditLandingpageReturnsTrueWhenUserHasPermissionAndNoOrganisationFilter(): void
    {
        $department = \Mockery::mock(DepartmentEntity::class);
        $department->shouldReceive('getSlug')->andReturn('foo');

        $expectedTemplatePath = 'public/department/custom/foo.html.twig';
        $this->twig->expects('getLoader->exists')->with($expectedTemplatePath)->andReturnFalse();

        $this->authorizationMatrix
            ->expects('isAuthorized')
            ->with('department_landing_page', 'update')
            ->andReturnTrue();

        $this->authorizationMatrix
            ->expects('hasFilter')
            ->with(AuthorizationMatrixFilter::ORGANISATION_ONLY)
            ->andReturnFalse();

        self::assertTrue(
            $this->departmentService->userCanEditLandingpage($department),
        );
    }

    public function testUserCanEditLandingpageReturnsFalseWhenUserHasPermissionButAnOrganisationFilterMismatch(): void
    {
        $department = \Mockery::mock(DepartmentEntity::class);
        $department->shouldReceive('getSlug')->andReturn('foo');

        $expectedTemplatePath = 'public/department/custom/foo.html.twig';
        $this->twig->expects('getLoader->exists')->with($expectedTemplatePath)->andReturnFalse();

        $this->authorizationMatrix
            ->expects('isAuthorized')
            ->with('department_landing_page', 'update')
            ->andReturnTrue();

        $this->authorizationMatrix
            ->expects('hasFilter')
            ->with(AuthorizationMatrixFilter::ORGANISATION_ONLY)
            ->andReturnTrue();

        $this->authorizationMatrix
            ->expects('getActiveOrganisation->hasDepartment')
            ->with($department)
            ->andReturnFalse();

        self::assertFalse(
            $this->departmentService->userCanEditLandingpage($department),
        );
    }

    public function testUserCanEditLandingpageReturnsTrueWhenUserHasPermissionButAndOrganisationFilterMatches(): void
    {
        $department = \Mockery::mock(DepartmentEntity::class);
        $department->shouldReceive('getSlug')->andReturn('foo');

        $expectedTemplatePath = 'public/department/custom/foo.html.twig';
        $this->twig->expects('getLoader->exists')->with($expectedTemplatePath)->andReturnFalse();

        $this->authorizationMatrix
            ->expects('isAuthorized')
            ->with('department_landing_page', 'update')
            ->andReturnTrue();

        $this->authorizationMatrix
            ->expects('hasFilter')
            ->with(AuthorizationMatrixFilter::ORGANISATION_ONLY)
            ->andReturnTrue();

        $this->authorizationMatrix
            ->expects('getActiveOrganisation->hasDepartment')
            ->with($department)
            ->andReturnTrue();

        self::assertTrue(
            $this->departmentService->userCanEditLandingpage($department),
        );
    }
}
