<?php

declare(strict_types=1);

namespace Shared\Domain\WooIndex;

use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WooIndexSitemap>
 */
class WooIndexSitemapRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WooIndexSitemap::class);
    }

    public function save(WooIndexSitemap $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(WooIndexSitemap $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function lastFinished(): ?WooIndexSitemap
    {
        /** @var ?WooIndexSitemap */
        return $this->createQueryBuilder('sm')
            ->where('sm.status = :status')
            ->orderBy('sm.createdAt', 'desc')
            ->setMaxResults(1)
            ->setParameter('status', WooIndexSitemapStatus::DONE)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return iterable<int,WooIndexSitemap>
     */
    public function getSitemapsForCleanup(int $treshold, DateTimeInterface $date): iterable
    {
        yield from $this->getAllFinishedSitemapsExceptForLast($treshold);
        yield from $this->getAllUnfinishedSitemapsOlderThan($date);
    }

    /**
     * @return iterable<int,WooIndexSitemap>
     */
    private function getAllFinishedSitemapsExceptForLast(int $treshold): iterable
    {
        return $this->createQueryBuilder('sm')
            ->where('sm.status = :status')
            ->orderBy('sm.createdAt', 'desc')
            ->setParameter('status', WooIndexSitemapStatus::DONE)
            ->setFirstResult($treshold)
            ->getQuery()
            ->toIterable();
    }

    /**
     * @return iterable<int,WooIndexSitemap>
     */
    private function getAllUnfinishedSitemapsOlderThan(DateTimeInterface $date): iterable
    {
        return $this->createQueryBuilder('sm')
            ->where('sm.status = :status')
            ->andWhere('sm.createdAt < :date')
            ->orderBy('sm.createdAt', 'desc')
            ->setParameter('status', WooIndexSitemapStatus::PROCESSING)
            ->setParameter('date', $date)
            ->getQuery()
            ->toIterable();
    }
}
