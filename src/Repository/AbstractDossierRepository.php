<?php

declare(strict_types=1);

namespace App\Repository;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\DossierStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AbstractDossier>
 *
 * @method AbstractDossier|null find($id, $lockMode = null, $lockVersion = null)
 * @method AbstractDossier|null findOneBy(array $criteria, array $orderBy = null)
 * @method AbstractDossier[]    findAll()
 * @method AbstractDossier[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AbstractDossierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AbstractDossier::class);
    }

    /**
     * @return AbstractDossier[]
     */
    public function findDossiersPendingPublication(): array
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.status IN (:statuses)')
            ->andWhere('d.completed = true')
            ->setParameter('statuses', [
                DossierStatus::CONCEPT,
                DossierStatus::SCHEDULED,
                DossierStatus::PREVIEW,
            ]);

        return $qb->getQuery()->getResult();
    }
}
