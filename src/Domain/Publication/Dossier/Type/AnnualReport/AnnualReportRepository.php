<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\AnnualReport;

use Doctrine\Persistence\ManagerRegistry;
use Shared\Domain\Publication\Dossier\Type\AbstractDossierRepository;
use Shared\Domain\Search\Result\Dossier\AnnualReport\AnnualReportSearchResult;
use Shared\Domain\Search\Result\Dossier\ProvidesDossierTypeSearchResultInterface;
use Shared\Service\Security\ApplicationMode\ApplicationMode;

/**
 * @extends AbstractDossierRepository<AnnualReport>
 */
class AnnualReportRepository extends AbstractDossierRepository implements ProvidesDossierTypeSearchResultInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnnualReport::class);
    }

    public function getSearchResultViewModel(
        string $prefix,
        string $dossierNr,
        ApplicationMode $mode,
    ): ?AnnualReportSearchResult {
        $qb = $this->createQueryBuilder('dos')
            ->select(sprintf(
                'new %s(
                    dos.id,
                    dos.dossierNr,
                    dos.documentPrefix,
                    dos.title,
                    dos.summary,
                    dos.publicationDate,
                    COUNT(att) + 1,
                    dos.dateFrom
                )',
                AnnualReportSearchResult::class,
            ))
            ->where('dos.documentPrefix = :prefix')
            ->andWhere('dos.dossierNr = :dossierNr')
            ->andWhere('dos.status IN (:statuses)')
            ->leftJoin('dos.attachments', 'att')
            ->groupBy('dos.id')
            ->setParameter('prefix', $prefix)
            ->setParameter('dossierNr', $dossierNr)
            ->setParameter('statuses', $mode->getAccessibleDossierStatuses())
        ;

        /** @var ?AnnualReportSearchResult */
        return $qb->getQuery()->getOneOrNullResult();
    }
}
