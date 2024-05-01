<?php

declare(strict_types=1);

namespace App\Repository;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\ViewModel\CovenantSearchEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Covenant>
 *
 * @method Covenant|null find($id, $lockMode = null, $lockVersion = null)
 * @method Covenant|null findOneBy(array $criteria, array $orderBy = null)
 * @method Covenant[]    findAll()
 * @method Covenant[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CovenantRepository extends ServiceEntityRepository
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

    public function getSearchEntry(string $prefix, string $dossierNr): ?CovenantSearchEntry
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
                CovenantSearchEntry::class,
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

        /** @var ?CovenantSearchEntry */
        return $qb->getQuery()->getOneOrNullResult();
    }
}
