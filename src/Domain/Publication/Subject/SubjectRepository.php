<?php

declare(strict_types=1);

namespace App\Domain\Publication\Subject;

use App\Domain\Organisation\Organisation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Subject>
 */
class SubjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subject::class);
    }

    public function save(Subject $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Subject $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getQueryForOrganisation(Organisation $organisation): Query
    {
        return $this->createQueryBuilder('sub')
            ->where('sub.organisation = :organisation')
            ->setParameter('organisation', $organisation)
            ->orderBy('sub.name', 'ASC')
            ->getQuery();
    }
}
