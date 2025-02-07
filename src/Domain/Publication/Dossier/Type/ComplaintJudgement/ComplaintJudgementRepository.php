<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\ComplaintJudgement;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\AbstractDossierRepository;
use App\Domain\Search\Result\Dossier\ComplaintJudgement\ComplaintJudgementSearchResult;
use App\Domain\Search\Result\Dossier\ProvidesDossierTypeSearchResultInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends AbstractDossierRepository<ComplaintJudgement>
 */
class ComplaintJudgementRepository extends AbstractDossierRepository implements ProvidesDossierTypeSearchResultInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ComplaintJudgement::class);
    }

    public function getSearchResultViewModel(string $prefix, string $dossierNr): ?ComplaintJudgementSearchResult
    {
        $qb = $this->createQueryBuilder('dos')
            ->select(sprintf(
                'new %s(
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
            ->setParameter('statuses', DossierStatus::PUBLISHED)
        ;

        /** @var ?ComplaintJudgementSearchResult */
        return $qb->getQuery()->getOneOrNullResult();
    }
}
