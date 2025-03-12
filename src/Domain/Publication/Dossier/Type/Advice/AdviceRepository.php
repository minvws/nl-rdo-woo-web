<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Advice;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\AbstractDossierRepository;
use App\Domain\Search\Result\Dossier\Advice\AdviceSearchResult;
use App\Domain\Search\Result\Dossier\ProvidesDossierTypeSearchResultInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends AbstractDossierRepository<Advice>
 */
class AdviceRepository extends AbstractDossierRepository implements ProvidesDossierTypeSearchResultInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Advice::class);
    }

    public function getSearchResultViewModel(string $prefix, string $dossierNr): ?AdviceSearchResult
    {
        $qb = $this->createQueryBuilder('dos')
            ->select(sprintf(
                'new %s(
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
            ->setParameter('statuses', DossierStatus::PUBLISHED)
        ;

        /** @var ?AdviceSearchResult */
        return $qb->getQuery()->getOneOrNullResult();
    }
}
