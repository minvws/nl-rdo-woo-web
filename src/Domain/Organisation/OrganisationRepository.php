<?php

declare(strict_types=1);

namespace Shared\Domain\Organisation;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Shared\Repository\PaginationQueryBuilder;

/**
 * @extends ServiceEntityRepository<Organisation>
 */
class OrganisationRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly PaginationQueryBuilder $paginationQueryBuilder,
    ) {
        parent::__construct($registry, Organisation::class);
    }

    /**
     * @return list<Organisation>
     */
    public function getPaginated(int $itemsPerPage, ?string $cursor): array
    {
        /** @var list<Organisation> */
        return $this->paginationQueryBuilder
            ->getPaginated(Organisation::class, $itemsPerPage, $cursor)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Organisation[]
     */
    public function getAllSortedByName(): array
    {
        return $this->createQueryBuilder('o')
            ->orderBy('o.name', 'asc')
            ->getQuery()
            ->getResult();
    }
}
