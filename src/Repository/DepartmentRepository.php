<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Department;
use App\Entity\Organisation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Department>
 */
class DepartmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Department::class);
    }

    public function save(Department $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Department $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return string[]
     */
    public function getNames(): array
    {
        /** @var string[] $names */
        $names = $this->createQueryBuilder('d')
            ->select('d.name')
            ->getQuery()
            ->getSingleColumnResult();

        return $names;
    }

    public function findPublicDepartmentBySlug(string $slug): Department
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.slug = :slug')
            ->andWhere('d.public = true')
            ->setParameter('slug', $slug);

        /** @var Department */
        return $qb->getQuery()->getSingleResult();
    }

    /**
     * @return array<array-key,Department>
     */
    public function getAllPublicDepartments(): array
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.public = true')
            ->orderBy('d.shortTag', 'ASC');

        /** @var array<array-key,Department> */
        return $qb->getQuery()->getResult();
    }

    public function countPublicDepartments(): int
    {
        $qb = $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.public = true');

        /** @var int */
        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return Department[]
     */
    public function getOrganisationDepartmentsSortedByName(Organisation $organisation): array
    {
        $qb = $this->createQueryBuilder('d')
            ->innerJoin('d.organisations', 'o')
            ->where('o.id = :organisationId')
            ->orderBy('d.name', 'asc')
            ->setParameter('organisationId', $organisation->getId());

        return $qb->getQuery()->getResult();
    }
}
