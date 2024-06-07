<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\ComplaintJudgement;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Search\Result\ComplaintJudgement\ComplaintJudgementSearchResult;
use App\Domain\Search\Result\ProvidesDossierTypeSearchResultInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<ComplaintJudgement>
 *
 * @method ComplaintJudgement|null find($id, $lockMode = null, $lockVersion = null)
 * @method ComplaintJudgement|null findOneBy(array $criteria, array $orderBy = null)
 * @method ComplaintJudgement[]    findAll()
 * @method ComplaintJudgement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ComplaintJudgementRepository extends ServiceEntityRepository implements ProvidesDossierTypeSearchResultInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ComplaintJudgement::class);
    }

    public function save(ComplaintJudgement $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ComplaintJudgement $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByDossierId(Uuid $dossierId): ComplaintJudgement
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.id = :dossierId')
            ->setParameter('dossierId', $dossierId);

        /** @var ComplaintJudgement */
        return $qb->getQuery()->getSingleResult();
    }

    public function getSearchResultViewModel(string $prefix, string $dossierNr): ?ComplaintJudgementSearchResult
    {
        $qb = $this->createQueryBuilder('dos')
            ->select(sprintf(
                'new %s(
                    dos.dossierNr,
                    dos.documentPrefix,
                    dos.title,
                    dos.summary,
                    dos.publicationDate,
                    1,
                    dos.dateFrom
                )',
                ComplaintJudgementSearchResult::class,
            ))
            ->where('dos.documentPrefix = :prefix')
            ->andWhere('dos.dossierNr = :dossierNr')
            ->andWhere('dos.status IN (:statuses)')
            ->groupBy('dos.id')
            ->setParameter('prefix', $prefix)
            ->setParameter('dossierNr', $dossierNr)
            ->setParameter('statuses', DossierStatus::PUBLISHED)
        ;

        /** @var ?ComplaintJudgementSearchResult */
        return $qb->getQuery()->getOneOrNullResult();
    }
}
