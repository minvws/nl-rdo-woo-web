<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\AbstractDossier;
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

    /**
     * @return list<T>
     */
    public function getByOrganisation(Organisation $organisation, int $itemsPerPage, ?string $cursor): array
    {
        $queryBuilder = $this->createQueryBuilder('dossier')
            ->where('dossier.organisation = :organisation')
            ->setParameter('organisation', $organisation);

        if ($cursor !== null) {
            $decodedCursor = \json_decode(\base64_decode($cursor), true);
            if (\is_array($decodedCursor) && \array_key_exists('id', $decodedCursor)) {
                $id = $decodedCursor['id'];

                $queryBuilder->andWhere('dossier.id > :id')
                    ->setParameter('id', $id);
            }
        }

        /** @var list<T> */
        return $queryBuilder
            ->orderBy('dossier.id', 'ASC')
            ->setMaxResults($itemsPerPage + 1)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ?T
     */
    public function findByOrganisationAndId(Organisation $organisation, Uuid $dossierId): ?AbstractDossier
    {
        /** @var ?T */
        return $this->createQueryBuilder('dossier')
            ->where('dossier.organisation = :organisation')
            ->setParameter('organisation', $organisation)
            ->andWhere('dossier.id = :dossierId')
            ->setParameter('dossierId', $dossierId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
