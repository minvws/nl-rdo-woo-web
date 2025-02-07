<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Repository;

use App\Domain\Publication\Dossier\Type\WooDecision\Entity\DocumentFileUpdate;
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
}
