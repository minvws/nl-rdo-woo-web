<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier;

use App\Domain\Publication\Dossier\Type\DossierType;
use App\Entity\Organisation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<AbstractDossier>
 *
 * @method AbstractDossier|null find($id, $lockMode = null, $lockVersion = null)
 * @method AbstractDossier|null findOneBy(array $criteria, array $orderBy = null)
 * @method AbstractDossier[]    findAll()
 * @method AbstractDossier[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AbstractDossierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AbstractDossier::class);
    }

    /**
     * @param DossierStatus[] $statuses
     * @param DossierType[]   $types
     */
    public function getDossiersForOrganisationQueryBuilder(
        Organisation $organisation,
        array $statuses,
        array $types,
    ): QueryBuilder {
        return $this->createQueryBuilder('dos')
            ->andWhere('dos.organisation = :organisation')->setParameter('organisation', $organisation)
            ->andWhere('dos.status IN (:statuses)')->setParameter('statuses', $statuses)
            ->andWhere('dos INSTANCE OF :types')->setParameter('types', $types);
    }

    /**
     * @return AbstractDossier[]
     */
    public function findBySearchTerm(string $searchTerm, int $limit, Organisation $organisation): array
    {
        $qb = $this->createQueryBuilder('d')
            ->where('ILIKE(d.title, :searchTerm) = true')
            ->orWhere('d.id IN (
                SELECT w.id
                FROM Domain:Publication\Dossier\Type\WooDecision\WooDecision w
                LEFT JOIN w.inquiries i
                WHERE ILIKE(i.casenr, :searchTerm) = true
            )')
            ->orWhere('ILIKE(d.dossierNr, :searchTerm) = true')
            ->andWhere('d.organisation = :organisation')
            ->orderBy('d.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->setParameter('organisation', $organisation)
        ;

        /** @var AbstractDossier[] */
        return $qb->getQuery()->getResult();
    }

    public function findOneByDossierId(Uuid $dossierId): AbstractDossier
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.id = :dossierId')
            ->setParameter('dossierId', $dossierId);

        /** @var AbstractDossier */
        return $qb->getQuery()->getSingleResult();
    }

    /**
     * @return AbstractDossier[]
     */
    public function findDossiersPendingPublication(): array
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.status IN (:statuses)')
            ->andWhere('d.completed = true')
            ->setParameter('statuses', [
                DossierStatus::CONCEPT,
                DossierStatus::SCHEDULED,
                DossierStatus::PREVIEW,
            ]);

        return $qb->getQuery()->getResult();
    }

    public function remove(AbstractDossier $dossier): void
    {
        $this->getEntityManager()->remove($dossier);
        $this->getEntityManager()->flush();
    }
}
