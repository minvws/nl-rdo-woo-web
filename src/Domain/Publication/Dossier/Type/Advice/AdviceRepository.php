<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\Advice;

use Doctrine\Persistence\ManagerRegistry;
use Shared\ApplicationId;
use Shared\Domain\Publication\Dossier\Type\AbstractDossierRepository;
use Shared\Domain\Search\Result\Dossier\Advice\AdviceSearchResult;
use Shared\Domain\Search\Result\Dossier\ProvidesDossierTypeSearchResultInterface;

use function sprintf;

/**
 * @extends AbstractDossierRepository<Advice>
 */
class AdviceRepository extends AbstractDossierRepository implements ProvidesDossierTypeSearchResultInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Advice::class);
    }

    public function getSearchResultViewModel(
        string $documentPrefix,
        string $dossierNumber,
        ApplicationId $applicationId,
    ): ?AdviceSearchResult {
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
                AdviceSearchResult::class,
            ))
            ->where('dos.documentPrefix = :documentPrefix')
            ->andWhere('dos.dossierNumber = :dossierNumber')
            ->andWhere('dos.status IN (:statuses)')
            ->leftJoin('dos.attachments', 'att')
            ->groupBy('dos.id')
            ->setParameter('documentPrefix', $documentPrefix)
            ->setParameter('dossierNumber', $dossierNumber)
            ->setParameter('statuses', $applicationId->getAccessibleDossierStatuses());

        /** @var ?AdviceSearchResult */
        return $qb->getQuery()->getOneOrNullResult();
    }
}
