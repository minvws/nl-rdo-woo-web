<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Repository;

use App\Domain\Publication\Dossier\Type\WooDecision\Entity\DocumentFileSet;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\Enum\DocumentFileSetStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

    public function findUncompletedByDossier(WooDecision $dossier): ?DocumentFileSet
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.dossier = :dossier')
            ->andWhere('d.status != :status')
            ->setParameter('dossier', $dossier)
            ->setParameter('status', DocumentFileSetStatus::COMPLETED)
        ;

        /** @var ?DocumentFileSet */
        return $qb->getQuery()->getOneOrNullResult();
    }
}
