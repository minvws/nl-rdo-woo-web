<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\ValueObject\ExternalId;
use Symfony\Component\Uid\Uuid;

use function array_key_exists;
use function base64_decode;
use function is_array;
use function json_decode;

/**
 * @template TDossier of AbstractDossier
 *
 * @extends ServiceEntityRepository<TDossier>
 *
 * @implements DossierRepositoryWithExternalId<TDossier>
 */
abstract class AbstractDossierRepository extends ServiceEntityRepository implements DossierRepositoryWithExternalId
{
    /**
     * @param TDossier $entity
     */
    public function save(AbstractDossier $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param TDossier $entity
     */
    public function remove(AbstractDossier $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return TDossier
     */
    public function findOneByDossierId(Uuid $dossierId): AbstractDossier
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.id = :dossierId')
            ->setParameter('dossierId', $dossierId);

        /** @var TDossier */
        return $qb->getQuery()->getSingleResult();
    }

    /**
     * @return list<TDossier>
     */
    public function getByOrganisationAndContainsExternalId(Organisation $organisation, int $itemsPerPage, ?string $cursor): array
    {
        $queryBuilder = $this->createQueryBuilder('dossier')
            ->where('dossier.organisation = :organisation')
            ->andWhere('dossier.externalId IS NOT NULL')
            ->setParameter('organisation', $organisation);

        if ($cursor !== null) {
            $decodedCursor = json_decode(base64_decode($cursor), true);
            if (is_array($decodedCursor) && array_key_exists('id', $decodedCursor)) {
                $id = $decodedCursor['id'];

                $queryBuilder->andWhere('dossier.id > :id')
                    ->setParameter('id', $id);
            }
        }

        /** @var list<TDossier> */
        return $queryBuilder
            ->orderBy('dossier.id', 'ASC')
            ->setMaxResults($itemsPerPage + 1)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ?TDossier
     */
    public function findByOrganisationAndExternalId(Organisation $organisation, ExternalId $externalId): ?AbstractDossier
    {
        /** @var ?TDossier */
        return $this->createQueryBuilder('dossier')
            ->where('dossier.organisation = :organisation')
            ->setParameter('organisation', $organisation)
            ->andWhere('dossier.externalId = :externalId')
            ->setParameter('externalId', $externalId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
