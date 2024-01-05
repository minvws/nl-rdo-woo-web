<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Dossier;
use App\Entity\Organisation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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
    public function findBySearchTerm(string $searchTerm, int $limit, Organisation $organisation): array
    {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.inquiries', 'i')
            ->where('d.title LIKE :searchTerm')
            ->orWhere('d.dossierNr LIKE :searchTerm')
            ->orWhere('i.casenr LIKE :searchTerm')
            ->andWhere('d.organisation = :organisation')
            ->orderBy('d.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->setParameter('organisation', $organisation)
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Dossier[]
     */
    public function findPendingPreviewDossiers(\DateTimeImmutable $date): array
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.status = :status')
            ->andWhere('d.completed = true')
            ->andWhere('d.previewDate <= :date')
            ->setParameter('status', Dossier::STATUS_SCHEDULED)
            ->setParameter('date', $date);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Dossier[]
     */
    public function findPendingPublishDossiers(\DateTimeImmutable $date): array
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.status IN (:statuses)')
            ->andWhere('d.completed = true')
            ->andWhere('d.publicationDate <= :date')
            ->setParameter('statuses', [
                Dossier::STATUS_PREVIEW,
                Dossier::STATUS_SCHEDULED,
            ])
            ->setParameter('date', $date);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Dossier[]
     */
    public function findAllForOrganisation(Organisation $organisation): array
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.organisation = :organisation')
            ->setParameter('organisation', $organisation)
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @param string[] $statuses
     */
    public function getDossiersForOrganisationQueryBuilder(Organisation $organisation, array $statuses): QueryBuilder
    {
        return $this->createQueryBuilder('dos')
            ->leftJoin('dos.inquiries', 'inq')
            ->addSelect('inq')
            ->andWhere('dos.organisation = :organisation')
            ->andWhere('dos.status IN (:statuses)')
            ->setParameter('organisation', $organisation)
            ->setParameter('statuses', $statuses);
    }
}
