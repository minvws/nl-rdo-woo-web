<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<AbstractDossier>
 */
class DossierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AbstractDossier::class);
    }

    /**
     * @param DossierStatus[] $statuses
     * @param DossierType[] $types
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

    public function findOneByDossierId(Uuid $dossierId): AbstractDossier
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.id = :dossierId')
            ->setParameter('dossierId', $dossierId);

        /** @var AbstractDossier */
        return $qb->getQuery()->getSingleResult();
    }

    public function findOneByPrefixAndDossierNr(string $prefix, string $dossierNr): AbstractDossier
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.documentPrefix = :prefix')
            ->andWhere('d.dossierNr = :dossierNr')
            ->setParameter('prefix', $prefix)
            ->setParameter('dossierNr', $dossierNr);

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

    /**
     * @return AbstractDossier[]
     */
    public function getRecentDossiers(int $limit, ?Department $department): array
    {
        $qb = $this->createQueryBuilder('dos')
            ->where('dos.status = :status')
            ->setParameter('status', DossierStatus::PUBLISHED)
            ->orderBy('dos.publicationDate', 'DESC')
            ->setMaxResults($limit);

        if ($department !== null) {
            $qb->innerJoin('dos.departments', 'dep')
                ->andWhere('dep = :department')
                ->setParameter('department', $department);
        }

        return $qb->getQuery()->getResult();
    }

    public function getAllDossierIdsQuery(): Query
    {
        return $this->createQueryBuilder('d')
            ->select('d.id')
            ->getQuery();
    }
}
