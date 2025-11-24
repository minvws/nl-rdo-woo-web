<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\Advice;

use Doctrine\Persistence\ManagerRegistry;
use Shared\Domain\Publication\Dossier\Type\AbstractDossierRepository;
use Shared\Domain\Search\Result\Dossier\Advice\AdviceSearchResult;
use Shared\Domain\Search\Result\Dossier\ProvidesDossierTypeSearchResultInterface;
use Shared\Service\Security\ApplicationMode\ApplicationMode;

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
        string $prefix,
        string $dossierNr,
        ApplicationMode $mode,
    ): ?AdviceSearchResult {
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
                AdviceSearchResult::class,
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

        /** @var ?AdviceSearchResult */
        return $qb->getQuery()->getOneOrNullResult();
    }
}
