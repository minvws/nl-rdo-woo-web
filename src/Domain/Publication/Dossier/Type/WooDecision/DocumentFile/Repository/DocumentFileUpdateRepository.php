<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Repository;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileSet;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileUpdate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DocumentFileUpdate>
 */
class DocumentFileUpdateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocumentFileUpdate::class);
    }

    public function save(DocumentFileUpdate $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function hasUpdateForFileSetAndDocument(DocumentFileSet $documentFileSet, Document $document): bool
    {
        $qb = $this->createQueryBuilder('u');
        $query = $qb
            ->select($qb->expr()->count('u'))
            ->where('u.documentFileSet = :documentFileSet')
            ->andWhere('u.document = :document')
            ->setParameter('documentFileSet', $documentFileSet)
            ->setParameter('document', $document)
            ->getQuery();

        return $query->getSingleScalarResult() === 1;
    }
}
