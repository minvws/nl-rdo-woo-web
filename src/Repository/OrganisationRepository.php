<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Organisation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Organisation>
 *
 * @method Organisation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Organisation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Organisation[]    findAll()
 * @method Organisation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrganisationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Organisation::class);
    }

    /**
     * @return Organisation[]
     */
    public function getAllSortedByName(): array
    {
        $qb = $this->createQueryBuilder('o')
            ->orderBy('o.name', 'asc');

        return $qb->getQuery()->getResult();
    }
}
