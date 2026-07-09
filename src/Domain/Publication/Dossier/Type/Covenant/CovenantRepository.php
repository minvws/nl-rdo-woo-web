<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\Covenant;

use Doctrine\Persistence\ManagerRegistry;
use Shared\Domain\Publication\Dossier\Type\AbstractDossierRepository;
use Shared\Domain\Search\Result\Dossier\Covenant\CovenantSearchResult;
use Shared\Domain\Search\Result\Dossier\ProvidesDossierTypeSearchResultInterface;
use Shared\Service\Security\ApplicationMode\ApplicationMode;

use function sprintf;

/**
 * @extends AbstractDossierRepository<Covenant>
 */
class CovenantRepository extends AbstractDossierRepository implements ProvidesDossierTypeSearchResultInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Covenant::class);
    }

    public function getSearchResultViewModel(
        string $prefix,
        string $dossierNumber,
        ApplicationMode $mode,
    ): ?CovenantSearchResult {
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
                    dos.dateFrom,
                    dos.dateTo
                )',
                CovenantSearchResult::class,
            ))
            ->where('dos.documentPrefix = :prefix')
            ->andWhere('dos.dossierNumber = :dossierNumber')
            ->andWhere('dos.status IN (:statuses)')
            ->leftJoin('dos.attachments', 'att')
            ->groupBy('dos.id')
            ->setParameter('prefix', $prefix)
            ->setParameter('dossierNumber', $dossierNumber)
            ->setParameter('statuses', $mode->getAccessibleDossierStatuses());

        /** @var ?CovenantSearchResult */
        return $qb->getQuery()->getOneOrNullResult();
    }
}
