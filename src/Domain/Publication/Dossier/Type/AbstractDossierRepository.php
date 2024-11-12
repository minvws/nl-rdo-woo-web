<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type;

use App\Domain\Publication\Dossier\AbstractDossier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Component\Uid\Uuid;

/**
 * @template T of AbstractDossier
 *
 * @extends ServiceEntityRepository<T>
 */
abstract class AbstractDossierRepository extends ServiceEntityRepository
{
    /**
     * @param T $entity
     */
    public function save(AbstractDossier $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param T $entity
     */
    public function remove(AbstractDossier $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return T
     */
    public function findOneByDossierId(Uuid $dossierId): AbstractDossier
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.id = :dossierId')
            ->setParameter('dossierId', $dossierId);

        /** @var T */
        return $qb->getQuery()->getSingleResult();
    }
}
