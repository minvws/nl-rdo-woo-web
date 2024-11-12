<?php

declare(strict_types=1);

namespace App\Repository;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Entity\ProductionReportProcessRun;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductionReportProcessRun>
 */
class ProductionReportProcessRunRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductionReportProcessRun::class);
    }

    /**
     * @return ProductionReportProcessRun[]
     */
    public function findExpiredRuns(): array
    {
        $qb = $this->createQueryBuilder('r')
            ->where('r.status = :comparing')
            ->orWhere('r.status = :updating')
            ->andWhere('r.startedAt < :expiryDate')
            ->setParameter('comparing', ProductionReportProcessRun::STATUS_COMPARING)
            ->setParameter('updating', ProductionReportProcessRun::STATUS_UPDATING)
            ->setParameter('expiryDate', new \DateTimeImmutable('-10 minutes'));

        return $qb->getQuery()->getResult();
    }

    public function save(ProductionReportProcessRun $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ProductionReportProcessRun $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function create(WooDecision $wooDecision): ProductionReportProcessRun
    {
        return new ProductionReportProcessRun($wooDecision);
    }
}
