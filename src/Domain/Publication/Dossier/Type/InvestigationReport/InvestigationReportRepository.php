<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\InvestigationReport;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Search\Result\Dossier\InvestigationReport\InvestigationReportSearchResult;
use App\Domain\Search\Result\Dossier\ProvidesDossierTypeSearchResultInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<InvestigationReport>
 *
 * @method InvestigationReport|null find($id, $lockMode = null, $lockVersion = null)
 * @method InvestigationReport|null findOneBy(array $criteria, array $orderBy = null)
 * @method InvestigationReport[]    findAll()
 * @method InvestigationReport[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InvestigationReportRepository extends ServiceEntityRepository implements ProvidesDossierTypeSearchResultInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InvestigationReport::class);
    }

    public function save(InvestigationReport $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(InvestigationReport $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByDossierId(Uuid $dossierId): InvestigationReport
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.id = :dossierId')
            ->setParameter('dossierId', $dossierId);

        /** @var InvestigationReport */
        return $qb->getQuery()->getSingleResult();
    }

    public function getSearchResultViewModel(string $prefix, string $dossierNr): ?InvestigationReportSearchResult
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
                InvestigationReportSearchResult::class,
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

        /** @var ?InvestigationReportSearchResult */
        return $qb->getQuery()->getOneOrNullResult();
    }
}
