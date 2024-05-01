<?php

declare(strict_types=1);

namespace App\Repository;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\ViewModel\DossierCounts;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WooDecision>
 *
 * @method WooDecision|null find($id, $lockMode = null, $lockVersion = null)
 * @method WooDecision|null findOneBy(array $criteria, array $orderBy = null)
 * @method WooDecision[]    findAll()
 * @method WooDecision[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WooDecisionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WooDecision::class);
    }

    public function getDossierCounts(WooDecision $dossier): DossierCounts
    {
        /** @var DossierCounts */
        return $this->createQueryBuilder('dos')
            ->select(sprintf(
                'new %s(
                    COUNT(doc),
                    COALESCE(SUM(doc.pageCount),0),
                    SUM(CASE WHEN doc.fileInfo.uploaded = true THEN 1 ELSE 0 END)
                )',
                DossierCounts::class,
            ))
            ->where('dos = :dossier')
            ->leftJoin('dos.documents', 'doc')
            ->groupBy('dos.id')
            ->setParameter('dossier', $dossier)
            ->getQuery()
            ->getSingleResult();
    }
}
