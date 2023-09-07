<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Dossier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Dossier>
 *
 * @method Dossier|null find($id, $lockMode = null, $lockVersion = null)
 * @method Dossier|null findOneBy(array $criteria, array $orderBy = null)
 * @method Dossier[]    findAll()
 * @method Dossier[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DossierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dossier::class);
    }

    public function save(Dossier $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Dossier $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Dossier[]
     */
    public function findAllPublishable(): array
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.status IN (:statuses)')
            ->setParameter('statuses', [
                Dossier::STATUS_PREVIEW,
                Dossier::STATUS_PUBLISHED,
            ]);

        $dossiers = $qb->getQuery()->getResult();

        return $dossiers;
    }

    /**
     * @return Dossier[]
     */
    public function findBySearchTerm(string $searchTerm, int $limit): array
    {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.inquiries', 'i')
            ->where('d.title LIKE :searchTerm')
            ->orWhere('d.dossierNr LIKE :searchTerm')
            ->orWhere('i.casenr LIKE :searchTerm')
            ->orderBy('d.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
        ;

        return $qb->getQuery()->getResult();
    }
}
