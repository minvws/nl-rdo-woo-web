<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\AbstractDossierRepository;
use App\Domain\Publication\Dossier\Type\DossierReference;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\ViewModel\DossierCounts;
use App\Domain\Search\Result\Dossier\ProvidesDossierTypeSearchResultInterface;
use App\Domain\Search\Result\Dossier\WooDecision\WooDecisionSearchResult;
use App\Entity\Organisation;
use App\Enum\ApplicationMode;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 *
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
            ->setParameter('statuses', DossierStatus::publiclyAvailableCases())
        ;

        return $qb->getQuery()->getResult();
    }

    public function getSearchResultViewModel(
        string $prefix,
        string $dossierNr,
        ApplicationMode $mode,
    ): ?WooDecisionSearchResult {
        $qb = $this->createQueryBuilder('dos')
            ->select(sprintf(
                'new %s(
                    dos.id,
                    dos.dossierNr,
                    dos.documentPrefix,
                    dos.title,
                    dos.decision,
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
            ->setParameter('statuses', $mode->getAccessibleDossierStatuses())
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

    public function findOne(Uuid $dossierId): WooDecision
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.id = :id')
            ->setParameter('id', $dossierId);

        /** @var WooDecision */
        return $qb->getQuery()->getSingleResult();
    }

    public function getDocumentsForBatchDownload(WooDecision $wooDecision): QueryBuilder
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('doc')
            ->from(Document::class, 'doc')
            ->join('doc.dossiers', 'dos')
            ->where('dos.id = :wooDecisionId')
            ->andWhere('dos.status IN (:statuses)')
            ->andWhere('doc.fileInfo.uploaded = true')
            ->andWhere('doc.suspended = false')
            ->andWhere('doc.withdrawn = false')
            ->andWhere('doc.judgement IN(:judgements)')
            ->setParameter('wooDecisionId', $wooDecision->getId())
            ->setParameter('statuses', DossierStatus::publiclyAvailableCases())
            ->setParameter('judgements', Judgement::atLeastPartialPublicValues());
    }

    /**
     * @return WooDecision[]
     */
    public function getPubliclyAvailable(): array
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.status IN (:statuses)')
            ->orderBy('d.publicationDate', 'ASC')
            ->setParameter('statuses', DossierStatus::publiclyAvailableCases())
        ;

        return $qb->getQuery()->getResult();
    }
}
