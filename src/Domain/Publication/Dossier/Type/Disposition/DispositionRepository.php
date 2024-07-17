<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Disposition;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Search\Result\Dossier\Disposition\DispositionSearchResult;
use App\Domain\Search\Result\Dossier\ProvidesDossierTypeSearchResultInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Disposition>
 *
 * @method Disposition|null find($id, $lockMode = null, $lockVersion = null)
 * @method Disposition|null findOneBy(array $criteria, array $orderBy = null)
 * @method Disposition[]    findAll()
 * @method Disposition[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DispositionRepository extends ServiceEntityRepository implements ProvidesDossierTypeSearchResultInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Disposition::class);
    }

    public function save(Disposition $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Disposition $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByDossierId(Uuid $dossierId): Disposition
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.id = :dossierId')
            ->setParameter('dossierId', $dossierId);

        /** @var Disposition */
        return $qb->getQuery()->getSingleResult();
    }

    public function getSearchResultViewModel(string $prefix, string $dossierNr): ?DispositionSearchResult
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
                DispositionSearchResult::class,
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

        /** @var ?DispositionSearchResult */
        return $qb->getQuery()->getOneOrNullResult();
    }
}
