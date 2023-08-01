<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\BatchDownload;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BatchDownload>
 *
 * @method BatchDownload|null find($id, $lockMode = null, $lockVersion = null)
 * @method BatchDownload|null findOneBy(array $criteria, array $orderBy = null)
 * @method BatchDownload[]    findAll()
 * @method BatchDownload[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BatchDownloadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BatchDownload::class);
    }

    public function save(BatchDownload $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(BatchDownload $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    //    /**
    //     * @return BatchDownload[] Returns an array of BatchDownload objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('b.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?BatchDownload
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * @return array<BatchDownload>
     */
    public function findExpiredArchives(): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.expiration < :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getResult();
    }
}
