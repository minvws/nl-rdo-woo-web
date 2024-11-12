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
