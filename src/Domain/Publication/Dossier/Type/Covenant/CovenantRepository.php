<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Covenant;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Search\Result\Dossier\Covenant\CovenantSearchResult;
use App\Domain\Search\Result\Dossier\ProvidesDossierTypeSearchResultInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Covenant>
 */
class CovenantRepository extends ServiceEntityRepository implements ProvidesDossierTypeSearchResultInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Covenant::class);
    }

    public function save(Covenant $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Covenant $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByDossierId(Uuid $dossierId): Covenant
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.id = :dossierId')
            ->setParameter('dossierId', $dossierId);

        /** @var Covenant */
        return $qb->getQuery()->getSingleResult();
    }

    public function getSearchResultViewModel(string $prefix, string $dossierNr): ?CovenantSearchResult
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
                    dos.dateFrom,
                    dos.dateTo
                )',
                CovenantSearchResult::class,
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

        /** @var ?CovenantSearchResult */
        return $qb->getQuery()->getOneOrNullResult();
    }
}
