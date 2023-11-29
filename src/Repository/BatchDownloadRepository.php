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

    public function save(BatchDownload $entity): void
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    public function remove(BatchDownload $entity): void
    {
        $this->getEntityManager()->remove($entity);
        $this->getEntityManager()->flush();
    }

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

    public function pruneExpired(): void
    {
        $this->createQueryBuilder('b')
            ->delete()
            ->andWhere('b.expiration < :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }
}
