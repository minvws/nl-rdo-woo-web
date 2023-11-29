<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\GovernmentOfficial;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GovernmentOfficial>
 *
 * @method GovernmentOfficial|null find($id, $lockMode = null, $lockVersion = null)
 * @method GovernmentOfficial|null findOneBy(array $criteria, array $orderBy = null)
 * @method GovernmentOfficial[]    findAll()
 * @method GovernmentOfficial[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GovernmentOfficialRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GovernmentOfficial::class);
    }

    public function save(GovernmentOfficial $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(GovernmentOfficial $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
