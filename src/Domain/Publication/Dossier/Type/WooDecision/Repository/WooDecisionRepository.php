<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Repository;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\AbstractDossierRepository;
use App\Domain\Publication\Dossier\Type\DossierReference;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\ViewModel\DossierCounts;
use App\Domain\Search\Result\Dossier\ProvidesDossierTypeSearchResultInterface;
use App\Domain\Search\Result\Dossier\WooDecision\WooDecisionSearchResult;
use App\Entity\Organisation;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends AbstractDossierRepository<WooDecision>
 */
class WooDecisionRepository extends AbstractDossierRepository implements ProvidesDossierTypeSearchResultInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WooDecision::class);
    }

    public function getDossierCounts(WooDecision $dossier): DossierCounts
    {
        /** @var DossierCounts */
        return $this->createQueryBuilder('dos')
            ->select(sprintf(
                'new %s(
                    COUNT(doc),
                    COALESCE(SUM(doc.pageCount),0),
                    SUM(CASE WHEN doc.fileInfo.uploaded = true THEN 1 ELSE 0 END)
                )',
                DossierCounts::class,
            ))
            ->where('dos = :dossier')
            ->leftJoin('dos.documents', 'doc')
            ->groupBy('dos.id')
            ->setParameter('dossier', $dossier)
            ->getQuery()
            ->getSingleResult();
    }

    /**
     * @return DossierReference[]
     */
    public function getDossierReferencesForDocument(string $documentNr): array
    {
        $qb = $this->createQueryBuilder('dos')
            ->select(sprintf(
                'new %s(dos.dossierNr, dos.documentPrefix, dos.title, :type)',
                DossierReference::class,
            ))
            ->where('doc.documentNr = :documentNr')
            ->andWhere('dos.status IN (:statuses)')
            ->innerJoin('dos.documents', 'doc')
            ->setParameter('documentNr', $documentNr)
            ->setParameter('type', DossierType::WOO_DECISION)
            ->setParameter('statuses', [DossierStatus::PREVIEW, DossierStatus::PUBLISHED])
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
                    COUNT(doc),
                    dos.publicationReason
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

        /** @var ?WooDecisionSearchResult */
        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @return WooDecision[]
     */
    public function findAllForOrganisation(Organisation $organisation): array
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.organisation = :organisation')
            ->setParameter('organisation', $organisation)
        ;

        return $qb->getQuery()->getResult();
    }
}
