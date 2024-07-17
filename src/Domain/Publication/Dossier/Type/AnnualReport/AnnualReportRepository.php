<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\AnnualReport;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Search\Result\Dossier\AnnualReport\AnnualReportSearchResult;
use App\Domain\Search\Result\Dossier\ProvidesDossierTypeSearchResultInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<AnnualReport>
 *
 * @method AnnualReport|null find($id, $lockMode = null, $lockVersion = null)
 * @method AnnualReport|null findOneBy(array $criteria, array $orderBy = null)
 * @method AnnualReport[]    findAll()
 * @method AnnualReport[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnnualReportRepository extends ServiceEntityRepository implements ProvidesDossierTypeSearchResultInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnnualReport::class);
    }

    public function save(AnnualReport $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AnnualReport $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByDossierId(Uuid $dossierId): AnnualReport
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.id = :dossierId')
            ->setParameter('dossierId', $dossierId);

        /** @var AnnualReport */
        return $qb->getQuery()->getSingleResult();
    }

    public function getSearchResultViewModel(string $prefix, string $dossierNr): ?AnnualReportSearchResult
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
                AnnualReportSearchResult::class,
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

        /** @var ?AnnualReportSearchResult */
        return $qb->getQuery()->getOneOrNullResult();
    }
}
