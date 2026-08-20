<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\RequestForAdvice;

use Doctrine\Persistence\ManagerRegistry;
use Shared\ApplicationId;
use Shared\Domain\Publication\Dossier\Type\AbstractDossierRepository;
use Shared\Domain\Search\Result\Dossier\ProvidesDossierTypeSearchResultInterface;
use Shared\Domain\Search\Result\Dossier\RequestForAdvice\RequestForAdviceSearchResult;

use function sprintf;

/**
 * @extends AbstractDossierRepository<RequestForAdvice>
 */
class RequestForAdviceRepository extends AbstractDossierRepository implements ProvidesDossierTypeSearchResultInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RequestForAdvice::class);
    }

    public function getSearchResultViewModel(
        string $documentPrefix,
        string $dossierNumber,
        ApplicationId $applicationId,
    ): ?RequestForAdviceSearchResult {
        $qb = $this->createQueryBuilder('dos')
            ->select(sprintf(
                'new %s(
                    dos.id,
                    dos.dossierNumber,
                    dos.documentPrefix,
                    dos.title,
                    dos.summary,
                    dos.publicationDate,
                    COUNT(att) + 1,
                    dos.dateFrom
                )',
                RequestForAdviceSearchResult::class,
            ))
            ->where('dos.documentPrefix = :documentPrefix')
            ->andWhere('dos.dossierNumber = :dossierNumber')
            ->andWhere('dos.status IN (:statuses)')
            ->leftJoin('dos.attachments', 'att')
            ->groupBy('dos.id')
            ->setParameter('documentPrefix', $documentPrefix)
            ->setParameter('dossierNumber', $dossierNumber)
            ->setParameter('statuses', $applicationId->getAccessibleDossierStatuses());

        /** @var ?RequestForAdviceSearchResult */
        return $qb->getQuery()->getOneOrNullResult();
    }
}
