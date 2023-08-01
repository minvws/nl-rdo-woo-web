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

    public function getDuration(string $label): int
    {
        $section = $this->findOneBy(['section' => $label]);
        if (! $section) {
            return 0;
        }

        return (int) round($section->getDuration() / $section->getCount());
    }

    public function updateStats(string $label, int $duration): void
    {
        // check if section already exists in database
        $section = $this->findOneBy(['section' => $label]);
        if (! $section) {
            $section = new WorkerStats();
            $section->setSection($label);
            $section->setCount(0);
            $section->setDuration(0);
            $this->save($section, true);
        }

        $this->createQueryBuilder('s')
            ->update()
            ->set('s.count', 's.count + 1')
            ->set('s.duration', 's.duration + :duration')
            ->where('s.section = :label')
            ->setParameter('label', $label)
            ->setParameter('duration', $duration)
            ->getQuery()
            ->execute()
        ;
    }

    //    /**
    //     * @return WorkerStats[] Returns an array of WorkerStats objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?WorkerStats
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
