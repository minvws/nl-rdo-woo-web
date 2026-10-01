<?php

declare(strict_types=1);

namespace Shared\Domain\Content\Page;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use SortDirection;

/**
 * @extends ServiceEntityRepository<ContentPage>
 */
class ContentPageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContentPage::class);
    }

    /**
     * @return list<ContentPage>
     */
    public function findAllSortedBySlug(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.slug', SortDirection::Ascending)
            ->getQuery()
            ->getResult();
    }

    public function save(ContentPage $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
