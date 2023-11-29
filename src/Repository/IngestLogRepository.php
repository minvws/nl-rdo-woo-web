<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\IngestLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<IngestLog>
 *
 * @method IngestLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method IngestLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method IngestLog[]    findAll()
 * @method IngestLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class IngestLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IngestLog::class);
    }

    public function save(IngestLog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(IngestLog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
