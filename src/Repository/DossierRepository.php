<?php

declare(strict_types=1);

namespace App\Repository;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Search\Result\Dossier\ProvidesDossierTypeSearchResultInterface;
use App\Domain\Search\Result\Dossier\WooDecision\WooDecisionSearchResult;
use App\Entity\Dossier;
use App\Entity\Organisation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @deprecated use AbstractDossierRepository or dossier type specific repositories instead
 *
 * @extends ServiceEntityRepository<Dossier>
 */
class DossierRepository extends ServiceEntityRepository implements ProvidesDossierTypeSearchResultInterface
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
    public function findAllForOrganisation(Organisation $organisation): array
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.organisation = :organisation')
            ->setParameter('organisation', $organisation)
        ;

        return $qb->getQuery()->getResult();
    }

    public function getSearchResultViewModel(string $prefix, string $dossierNr): ?WooDecisionSearchResult
    {
        $qb = $this->createQueryBuilder('dos')
            ->select(sprintf(
                'new %s(
                    dos.dossierNr,
                    dos.documentPrefix,
                    dos.title, dos.decision,
                    dos.summary,
                    dos.publicationDate,
                    dos.decisionDate,
                    COUNT(doc)
                )',
                WooDecisionSearchResult::class,
            ))
            ->where('dos.documentPrefix = :prefix')
            ->andWhere('dos.dossierNr = :dossierNr')
            ->andWhere('dos.status IN (:statuses)')
            ->leftJoin('dos.documents', 'doc')
            ->groupBy('dos.id')
            ->setParameter('prefix', $prefix)
            ->setParameter('dossierNr', $dossierNr)
            ->setParameter('statuses', [DossierStatus::PREVIEW, DossierStatus::PUBLISHED])
        ;

        /** @var ?WooDecisionSearchResult $result */
        $result = $qb->getQuery()->getOneOrNullResult();

        return $result;
    }

    public function findOneByDossierId(Uuid $dossierId): WooDecision
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.id = :dossierId')
            ->setParameter('dossierId', $dossierId);

        /** @var WooDecision */
        return $qb->getQuery()->getSingleResult();
    }
}
