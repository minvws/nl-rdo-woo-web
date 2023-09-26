<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\WorkerStats;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkerStats>
 *
 * @method WorkerStats|null find($id, $lockMode = null, $lockVersion = null)
 * @method WorkerStats|null findOneBy(array $criteria, array $orderBy = null)
 * @method WorkerStats[]    findAll()
 * @method WorkerStats[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
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
}
