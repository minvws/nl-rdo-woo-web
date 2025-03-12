<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\OtherPublication;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\AbstractDossierRepository;
use App\Domain\Search\Result\Dossier\OtherPublication\OtherPublicationSearchResult;
use App\Domain\Search\Result\Dossier\ProvidesDossierTypeSearchResultInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends AbstractDossierRepository<OtherPublication>
 */
class OtherPublicationRepository extends AbstractDossierRepository implements ProvidesDossierTypeSearchResultInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OtherPublication::class);
    }

    public function getSearchResultViewModel(string $prefix, string $dossierNr): ?OtherPublicationSearchResult
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
                OtherPublicationSearchResult::class,
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

        /** @var ?OtherPublicationSearchResult */
        return $qb->getQuery()->getOneOrNullResult();
    }
}
