<?php

declare(strict_types=1);

namespace App\Domain\Organisation;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Organisation>
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
