<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Entity\Department;
use App\Repository\DepartmentRepository;
use App\Tests\Factory\DepartmentFactory;
use App\Tests\Factory\OrganisationFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Webmozart\Assert\Assert;
use Zenstruck\Foundry\Persistence\Proxy;

final class DepartmentRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

    private DepartmentRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->repository = self::getContainer()->get(DepartmentRepository::class);
    }

    public function testFindPublicDepartmentBySlug(): void
    {
        $department = DepartmentFactory::createOne([
            'name' => 'Foo',
            'slug' => 'foo',
        ]);

        self::assertEquals(
            $department->getId(),
            $this->repository->findPublicDepartmentBySlug('foo')->getId(),
        );

        $this->expectException(NoResultException::class);
        $this->repository->findPublicDepartmentBySlug('bar');
    }

    public function testGetAllPublicDepartments(): void
    {
        $this->deleteAllDepartments();
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
        $this->deleteAllDepartments();
        $this->createDepartments();

        self::assertSame(3, $this->repository->countPublicDepartments());
    }

    public function testGetOrganisationDepartmentsSortedByName(): void
    {
        $this->deleteAllDepartments();
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
        $this->deleteAllDepartments();
        $this->createDepartments();

        $firstDepartment = $this->repository->getAllPublicDepartments()[0] ?? null;
        Assert::notNull($firstDepartment);

        $firstId = $firstDepartment->getId();

        $result = $this->repository->findOne($firstId);

        self::assertEquals($firstDepartment, $result);
    }

    public function testGetDepartmentsQueryFilteredByOrganisation(): void
    {
        $organisationA = OrganisationFactory::createOne([]);
        $organisationB = OrganisationFactory::createOne([]);

        $departmentA = DepartmentFactory::createOne([
            'name' => 'department  A',
            'shortTag' => 'A',
            'slug' => 'a',
            'public' => true,
            'organisations' => [$organisationA],
        ]);

        $departmentB = DepartmentFactory::createOne([
            'name' => 'department  B',
            'shortTag' => 'B',
            'slug' => 'b',
            'public' => true,
            'organisations' => [$organisationB],
        ]);

        $departmentC = DepartmentFactory::createOne([
            'name' => 'department  C',
            'shortTag' => 'C',
            'slug' => 'c',
            'public' => true,
            'organisations' => [$organisationA],
        ]);

        /** @var iterable<Department> $departments */
        $departments = $this->repository->getDepartmentsQuery($organisationA->_real())->getResult();

        self::assertContains($departmentA->_real(), $departments);
        self::assertNotContains($departmentB->_real(), $departments);
        self::assertContains($departmentC->_real(), $departments);
    }

    public function testFindAllSortedByName(): void
    {
        $this->deleteAllDepartments();

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

    private function deleteAllDepartments(): void
    {
        $this->repository
            ->createQueryBuilder('d')
            ->delete()
            ->getQuery()
            ->execute();
    }

    /**
     * @return array<array-key,Department&Proxy<Department>>
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
