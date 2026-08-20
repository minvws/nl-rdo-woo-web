<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\ComplaintJudgement;

use Doctrine\Persistence\ManagerRegistry;
use Shared\ApplicationId;
use Shared\Domain\Publication\Dossier\Type\AbstractDossierRepository;
use Shared\Domain\Search\Result\Dossier\ComplaintJudgement\ComplaintJudgementSearchResult;
use Shared\Domain\Search\Result\Dossier\ProvidesDossierTypeSearchResultInterface;

use function sprintf;

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
        string $documentPrefix,
        string $dossierNumber,
        ApplicationId $applicationId,
    ): ?ComplaintJudgementSearchResult {
        $qb = $this->createQueryBuilder('dos')
            ->select(sprintf(
                'new %s(
                    dos.id,
                    dos.dossierNumber,
                    dos.documentPrefix,
                    dos.title,
                    dos.summary,
                    dos.publicationDate,
                    1,
                    dos.dateFrom
                )',
                ComplaintJudgementSearchResult::class,
            ))
            ->where('dos.documentPrefix = :documentPrefix')
            ->andWhere('dos.dossierNumber = :dossierNumber')
            ->andWhere('dos.status IN (:statuses)')
            ->groupBy('dos.id')
            ->setParameter('documentPrefix', $documentPrefix)
            ->setParameter('dossierNumber', $dossierNumber)
            ->setParameter('statuses', $applicationId->getAccessibleDossierStatuses());

        /** @var ?ComplaintJudgementSearchResult */
        return $qb->getQuery()->getOneOrNullResult();
    }
}
