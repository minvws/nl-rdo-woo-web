<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\InventoryProcessRun;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InventoryProcessRun>
 *
 * @method InventoryProcessRun|null find($id, $lockMode = null, $lockVersion = null)
 * @method InventoryProcessRun|null findOneBy(array $criteria, array $orderBy = null)
 * @method InventoryProcessRun[]    findAll()
 * @method InventoryProcessRun[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InventoryProcessRunRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InventoryProcessRun::class);
    }

    /**
     * @return InventoryProcessRun[]
     */
    public function findExpiredRuns(): array
    {
        $qb = $this->createQueryBuilder('r')
            ->where('r.status = :comparing')
            ->orWhere('r.status = :updating')
            ->andWhere('r.startedAt < :expiryDate')
            ->setParameter('comparing', InventoryProcessRun::STATUS_COMPARING)
            ->setParameter('updating', InventoryProcessRun::STATUS_UPDATING)
            ->setParameter('expiryDate', new \DateTimeImmutable('-10 minutes'));

        return $qb->getQuery()->getResult();
    }

    public function save(InventoryProcessRun $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(InventoryProcessRun $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
