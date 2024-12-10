<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier;

use App\Entity\Organisation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DocumentPrefix>
 */
class DocumentPrefixRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocumentPrefix::class);
    }

    public function save(DocumentPrefix $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(DocumentPrefix $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return array<DocumentPrefix>
     */
    public function findAllForOrganisation(Organisation $organisation): array
    {
        return $this->createQueryBuilder('d')
            ->join('d.organisation', 'o')
            ->andWhere('o.id = :val')
            ->setParameter('val', $organisation->getId())
            ->orderBy('d.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
