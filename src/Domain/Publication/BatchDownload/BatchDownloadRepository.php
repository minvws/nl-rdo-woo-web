<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\BatchDownload;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<BatchDownload>
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
    public function findExpiredBatchDownloads(): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.expiration < :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getResult();
    }

    public function getBestAvailableBatchDownloadForScope(BatchDownloadScope $scope): ?BatchDownload
    {
        $builder = $this->getBaseScopeQuery($scope);
        $builder->addSelect('(CASE
                    WHEN b.status = :completed THEN 3
                    WHEN b.status = :outdated THEN 2
                    WHEN b.status = :pending THEN 1
                    ELSE 0 END
                ) as hidden priority');

        $builder
            ->andWhere('b.expiration > :now')
            ->andWhere('b.status != :failed')
            ->setParameter('completed', BatchDownloadStatus::COMPLETED)
            ->setParameter('outdated', BatchDownloadStatus::OUTDATED)
            ->setParameter('pending', BatchDownloadStatus::PENDING)
            ->setParameter('failed', BatchDownloadStatus::FAILED)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('priority', 'DESC')
            ->addOrderBy('b.expiration', 'DESC')
            ->setMaxResults(1);

        /** @var ?BatchDownload */
        return $builder
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return BatchDownload[]
     */
    public function getAllForScope(BatchDownloadScope $scope): array
    {
        /** @var BatchDownload[] */
        return $this->getBaseScopeQuery($scope)->getQuery()->getResult();
    }

    public function exists(Uuid $id): bool
    {
        return $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->where('b.id = :id')
            ->setParameter('id', $id->toRfc4122())
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    private function getBaseScopeQuery(BatchDownloadScope $scope): QueryBuilder
    {
        $builder = $this->createQueryBuilder('b');

        if ($scope->inquiry instanceof Inquiry && $scope->wooDecision instanceof WooDecision) {
            return $builder
                ->andWhere('b.inquiry = :inquiry')
                ->setParameter('inquiry', $scope->inquiry)
                ->andWhere('b.dossier = :wooDecision')
                ->setParameter('wooDecision', $scope->wooDecision);
        }

        if ($scope->inquiry instanceof Inquiry) {
            return $builder
                ->andWhere('b.inquiry = :inquiry')
                ->setParameter('inquiry', $scope->inquiry)
                ->andWhere('b.dossier IS NULL');
        }

        return $builder
            ->andWhere('b.dossier = :wooDecision')
            ->setParameter('wooDecision', $scope->wooDecision)
            ->andWhere('b.inquiry IS NULL');
    }
}
