<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Repository;

use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileSet;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum\DocumentFileSetStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum\DocumentFileUpdateStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum\DocumentFileUploadStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DocumentFileSet>
 */
class DocumentFileSetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocumentFileSet::class);
    }

    public function save(DocumentFileSet $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(DocumentFileSet $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findUncompletedByDossier(WooDecision $dossier): ?DocumentFileSet
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.dossier = :dossier')
            ->andWhere('d.status NOT IN (:statuses)')
            ->setParameter('dossier', $dossier)
            ->setParameter('statuses', DocumentFileSetStatus::getFinalStatusValues())
        ;

        /** @var ?DocumentFileSet */
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function updateStatusTransactionally(DocumentFileSet $documentFileSet, DocumentFileSetStatus $status): void
    {
        $entityManager = $this->getEntityManager();

        $entityManager->getConnection()->beginTransaction();
        try {
            $entityManager->lock($documentFileSet, LockMode::PESSIMISTIC_WRITE);

            $documentFileSet->setStatus($status);
            $this->save($documentFileSet, true);

            $entityManager->commit();
        } catch (\Exception $e) {
            $entityManager->getConnection()->rollBack();
            throw $e;
        }
    }

    public function countUploadsToProcess(DocumentFileSet $documentFileSet): int
    {
        return intval($this->createQueryBuilder('d')
            ->select('count(u.id)')
            ->join('d.uploads', 'u')
            ->where('d = :documentFileSet')
            ->andWhere('u.status NOT IN(:statuses)')
            ->setParameter('documentFileSet', $documentFileSet)
            ->setParameter('statuses', DocumentFileUploadStatus::finalStatuses())
            ->getQuery()
            ->getSingleScalarResult());
    }

    public function countUpdatesToProcess(DocumentFileSet $documentFileSet): int
    {
        return intval($this->createQueryBuilder('d')
            ->select('count(u.id)')
            ->join('d.updates', 'u')
            ->where('d = :documentFileSet')
            ->andWhere('u.status != :status')
            ->setParameter('documentFileSet', $documentFileSet)
            ->setParameter('status', DocumentFileUpdateStatus::COMPLETED)
            ->getQuery()
            ->getSingleScalarResult());
    }

    /**
     * @return list<DocumentFileSet>
     */
    public function findAllWithFinalStatus(): array
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.status IN (:statuses)')
            ->setParameter('statuses', DocumentFileSetStatus::getFinalStatusValues())
        ;

        return $qb->getQuery()->getResult();
    }
}
