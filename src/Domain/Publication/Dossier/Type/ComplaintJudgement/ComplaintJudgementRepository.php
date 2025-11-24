<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\ComplaintJudgement;

use Doctrine\Persistence\ManagerRegistry;
use Shared\Domain\Publication\Dossier\Type\AbstractDossierRepository;
use Shared\Domain\Search\Result\Dossier\ComplaintJudgement\ComplaintJudgementSearchResult;
use Shared\Domain\Search\Result\Dossier\ProvidesDossierTypeSearchResultInterface;
use Shared\Service\Security\ApplicationMode\ApplicationMode;

/**
 * @extends AbstractDossierRepository<ComplaintJudgement>
 */
class ComplaintJudgementRepository extends AbstractDossierRepository implements ProvidesDossierTypeSearchResultInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ComplaintJudgement::class);
    }

    public function getSearchResultViewModel(
        string $prefix,
        string $dossierNr,
        ApplicationMode $mode,
    ): ?ComplaintJudgementSearchResult {
        $qb = $this->createQueryBuilder('dos')
            ->select(sprintf(
                'new %s(
                    dos.id,
                    dos.dossierNr,
                    dos.documentPrefix,
                    dos.title,
                    dos.summary,
                    dos.publicationDate,
                    1,
                    dos.dateFrom
                )',
                ComplaintJudgementSearchResult::class,
            ))
            ->where('dos.documentPrefix = :prefix')
            ->andWhere('dos.dossierNr = :dossierNr')
            ->andWhere('dos.status IN (:statuses)')
            ->groupBy('dos.id')
            ->setParameter('prefix', $prefix)
            ->setParameter('dossierNr', $dossierNr)
            ->setParameter('statuses', $mode->getAccessibleDossierStatuses())
        ;

        /** @var ?ComplaintJudgementSearchResult */
        return $qb->getQuery()->getOneOrNullResult();
    }
}
