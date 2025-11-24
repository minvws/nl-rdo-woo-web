<?php

declare(strict_types=1);

namespace Shared\Service\Stats;

use Carbon\CarbonImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkerStats>
 */
class WorkerStatsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkerStats::class);
    }

    public function save(WorkerStats $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(WorkerStats $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function removeOldEntries(CarbonImmutable $cutoffDate): void
    {
        $this->createQueryBuilder('w')
            ->delete()
            ->where('w.createdAt < :cutoffDate')
            ->setParameter('cutoffDate', $cutoffDate)
            ->getQuery()
            ->execute();
    }
}
