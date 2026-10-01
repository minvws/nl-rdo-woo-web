<?php

declare(strict_types=1);

namespace Shared\Domain\Organisation;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Shared\Repository\PaginationQueryBuilder;
use SortDirection;
use Webmozart\Assert\Assert;

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
     * @return array<array-key, Organisation>
     */
    public function getAllSortedByName(): array
    {
        return $this->createQueryBuilder('o')
            ->orderBy('o.name', SortDirection::Ascending)
            ->getQuery()
            ->getResult();
    }

    public function hasAny(): bool
    {
        return $this->createQueryBuilder('o')
            ->select('o.id')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult() !== null;
    }

    public function oldest(): ?Organisation
    {
        $result = $this->createQueryBuilder('o')
            ->setMaxResults(1)
            ->orderBy('o.createdAt', SortDirection::Ascending)
            ->getQuery()
            ->getOneOrNullResult();

        Assert::nullOrIsInstanceOf($result, Organisation::class);

        return $result;
    }

    /**
     * @return ArrayCollection<array-key,Organisation>
     */
    public function getLimited(int $limit = 100): ArrayCollection
    {
        $result = $this->createQueryBuilder('o')
            ->setMaxResults($limit)
            ->orderBy('o.createdAt', SortDirection::Ascending)
            ->getQuery()
            ->getResult();

        Assert::allIsInstanceOf($result, Organisation::class);

        return new ArrayCollection($result);
    }

    public function save(Organisation $organisation, bool $flush = false): void
    {
        $this->getEntityManager()->persist($organisation);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
