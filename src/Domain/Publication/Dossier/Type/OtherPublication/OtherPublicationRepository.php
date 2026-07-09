<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\OtherPublication;

use Doctrine\Persistence\ManagerRegistry;
use Shared\Domain\Publication\Dossier\Type\AbstractDossierRepository;
use Shared\Domain\Search\Result\Dossier\OtherPublication\OtherPublicationSearchResult;
use Shared\Domain\Search\Result\Dossier\ProvidesDossierTypeSearchResultInterface;
use Shared\Service\Security\ApplicationMode\ApplicationMode;

use function sprintf;

/**
 * @extends AbstractDossierRepository<OtherPublication>
 */
class OtherPublicationRepository extends AbstractDossierRepository implements ProvidesDossierTypeSearchResultInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OtherPublication::class);
    }

    public function getSearchResultViewModel(
        string $prefix,
        string $dossierNumber,
        ApplicationMode $mode,
    ): ?OtherPublicationSearchResult {
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
                OtherPublicationSearchResult::class,
            ))
            ->where('dos.documentPrefix = :prefix')
            ->andWhere('dos.dossierNumber = :dossierNumber')
            ->andWhere('dos.status IN (:statuses)')
            ->leftJoin('dos.attachments', 'att')
            ->groupBy('dos.id')
            ->setParameter('prefix', $prefix)
            ->setParameter('dossierNumber', $dossierNumber)
            ->setParameter('statuses', $mode->getAccessibleDossierStatuses());

        /** @var ?OtherPublicationSearchResult */
        return $qb->getQuery()->getOneOrNullResult();
    }
}
