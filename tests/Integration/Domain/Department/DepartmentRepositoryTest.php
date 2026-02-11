<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Department;

use Doctrine\ORM\NoResultException;
use Shared\Domain\Department\Department;
use Shared\Domain\Department\DepartmentRepository;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Webmozart\Assert\Assert;

use function array_map;

final class DepartmentRepositoryTest extends SharedWebTestCase
{
    private DepartmentRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->repository = self::getContainer()->get(DepartmentRepository::class);
    }

    public function testFindPublicDepartmentBySlug(): void
    {
        $slug = $this->getFaker()->unique()->slug();

        $department = DepartmentFactory::createOne([
            'slug' => $slug,
        ]);

        self::assertEquals(
            $department->getId(),
            $this->repository->findPublicDepartmentBySlug($slug)->getId(),
        );

        $this->expectException(NoResultException::class);
        $this->repository->findPublicDepartmentBySlug($this->faker->unique()->slug());
    }

    public function testGetAllPublicDepartments(): void
    {
        $this->createDepartments();

        $departments = $this->repository->getAllPublicDepartments();

        self::assertCount(3, $departments);

        $departments = array_map(fn (Department $department): array => [
            'name' => $department->getName(),
            'shortTag' => $department->getShortTag(),
            'slug' => $department->getSlug(),
            'public' => $department->isPublic(),
        ], $departments);

        $this->assertMatchesYamlSnapshot($departments);
    }

    public function testCountPublicDepartments(): void
    {
        $this->createDepartments();

        self::assertSame(3, $this->repository->countPublicDepartments());
    }

    public function testGetPaginatedWithCreatingExtra(): void
    {
        $departmentCount = $this->getFaker()->numberBetween(0, 5);
        DepartmentFactory::createMany($departmentCount);

        $result = $this->repository->getPaginated(100, null);

        self::assertCount($departmentCount, $result);
        self::assertContainsOnlyInstancesOf(Department::class, $result);
    }

    public function testGetOrganisationDepartmentsSortedByName(): void
    {
        $departments = $this->createDepartments();
        $organisation = OrganisationFactory::createOne([
            'departments' => $departments,
        ]);

        $departments = $this->repository->getOrganisationDepartmentsSortedByName($organisation);

        self::assertCount(4, $departments);

        $departments = array_map(fn (Department $department): array => [
            'name' => $department->getName(),
            'shortTag' => $department->getShortTag(),
            'slug' => $department->getSlug(),
            'public' => $department->isPublic(),
        ], $departments);

        $this->assertMatchesYamlSnapshot($departments);
    }

    public function testFindOne(): void
    {
        $this->createDepartments();

        $firstDepartment = $this->repository->getAllPublicDepartments()[0] ?? null;
        Assert::notNull($firstDepartment);

        $firstId = $firstDepartment->getId();

        $result = $this->repository->findOne($firstId);

        self::assertEquals($firstDepartment, $result);
    }

    public function testGetDepartmentsQueryFilteredByOrganisation(): void
    {
        $organisationA = OrganisationFactory::createOne();
        $organisationB = OrganisationFactory::createOne();

        $departmentA = DepartmentFactory::createOne([
            'public' => true,
            'organisations' => [$organisationA],
        ]);

        $departmentB = DepartmentFactory::createOne([
            'public' => true,
            'organisations' => [$organisationB],
        ]);

        $departmentC = DepartmentFactory::createOne([
            'public' => true,
            'organisations' => [$organisationA],
        ]);

        /** @var iterable<Department> $departments */
        $departments = $this->repository->getDepartmentsQuery($organisationA)->getResult();

        self::assertContains($departmentA, $departments);
        self::assertNotContains($departmentB, $departments);
        self::assertContains($departmentC, $departments);
    }

    public function testFindAllSortedByName(): void
    {
        DepartmentFactory::createSequence([
            ['name' => 'c'],
            ['name' => 'a'],
            ['name' => 'b'],
        ]);

        $departments = $this->repository->findAllSortedByName();

        self::assertCount(3, $departments);
        self::assertSame('a', $departments[0]->getName());
        self::assertSame('b', $departments[1]->getName());
        self::assertSame('c', $departments[2]->getName());
    }

    /**
     * @return Department[]
     */
    private function createDepartments(): array
    {
        return DepartmentFactory::new()
            ->sequence([
                [
                    'name' => 'Lorem Ipsum',
                    'shortTag' => 'li',
                    'slug' => 'lorem-ipsum',
                    'public' => true,
                ],
                [
                    'name' => 'Dolor Sit',
                    'shortTag' => 'ds',
                    'slug' => 'dolor-sit',
                    'public' => false,
                ],
                [
                    'name' => 'Amet Consectetur',
                    'shortTag' => 'ac',
                    'slug' => 'amet-consectetur',
                    'public' => true,
                ],
                [
                    'name' => 'Adipiscing Elit',
                    'shortTag' => 'ae',
                    'slug' => 'adipiscing-elit',
                    'public' => true,
                ],
            ])
            ->create();
    }
}
