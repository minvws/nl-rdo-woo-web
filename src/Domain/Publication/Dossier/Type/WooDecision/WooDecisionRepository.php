<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\AbstractDossierRepository;
use Shared\Domain\Publication\Dossier\Type\DossierReference;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ViewModel\DossierCounts;
use Shared\Domain\Search\Result\Dossier\ProvidesDossierTypeSearchResultInterface;
use Shared\Domain\Search\Result\Dossier\WooDecision\WooDecisionSearchResult;
use Shared\Service\Security\ApplicationMode\ApplicationMode;
use Symfony\Component\Uid\Uuid;

use function sprintf;

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
                    SUM(CASE WHEN doc.judgement IN (:judgements) THEN 1 ELSE 0 END)
                )',
                DossierCounts::class,
            ))
            ->where('dos = :dossier')
            ->leftJoin('dos.documents', 'doc')
            ->groupBy('dos.id')
            ->setParameter('dossier', $dossier)
            ->setParameter('judgements', Judgement::atLeastPartialPublicValues())
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
            ->setParameter('statuses', DossierStatus::publiclyAvailableCases());

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
            ->setParameter('statuses', $mode->getAccessibleDossierStatuses());

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
            ->setParameter('organisation', $organisation);

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
            ->setParameter('statuses', DossierStatus::publiclyAvailableCases());

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array{missing_uploads: int, withdrawn: int, suspended: int}
     */
    public function getNotificationCounts(WooDecision $wooDecision): array
    {
        $qb = $this->createQueryBuilder('d')
            ->select('SUM(CASE WHEN doc.withdrawn = true THEN 1 ELSE 0 END) as withdrawn')
            ->addSelect('SUM(CASE WHEN doc.suspended = true THEN 1 ELSE 0 END) as suspended')
            ->addSelect('SUM(CASE WHEN
                            doc.suspended = false
                            AND doc.withdrawn = false
                            AND doc.fileInfo.uploaded = false
                            AND doc.judgement
                        IN (:judgements) THEN 1 ELSE 0 END) as missing_uploads')
            ->where('d.id = :dossierId')
            ->leftJoin('d.documents', 'doc')
            ->setParameter('dossierId', $wooDecision->getId())
            ->setParameter('judgements', Judgement::atLeastPartialPublicValues());

        /**
         * @var array{missing_uploads: int, withdrawn: int, suspended: int}
         */
        return $qb->getQuery()->getSingleResult();
    }
}
