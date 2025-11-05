<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier;

use App\Domain\Organisation\Organisation;
use App\Repository\PaginationQueryBuilder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<DocumentPrefix>
 */
class DocumentPrefixRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly PaginationQueryBuilder $paginationQueryBuilder,
    ) {
        parent::__construct($registry, DocumentPrefix::class);
    }

    public function save(DocumentPrefix $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(DocumentPrefix $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return array<DocumentPrefix>
     */
    public function findAllForOrganisation(Organisation $organisation): array
    {
        return $this->createQueryBuilder('d')
            ->join('d.organisation', 'o')
            ->andWhere('o.id = :val')
            ->setParameter('val', $organisation->getId())
            ->orderBy('d.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByOrganisationAndId(Organisation $organisation, Uuid $documentPrefixId): ?DocumentPrefix
    {
        /** @var ?DocumentPrefix */
        return $this->createQueryBuilder('document_prefix')
            ->where('document_prefix.organisation = :organisation')
            ->setParameter('organisation', $organisation)
            ->andWhere('document_prefix.id = :document_prefix_id')
            ->setParameter('document_prefix_id', $documentPrefixId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<DocumentPrefix>
     */
    public function getByOrganisation(Organisation $organisation, int $itemsPerPage, ?string $cursor): array
    {
        /** @var list<DocumentPrefix> */
        return $this->paginationQueryBuilder
            ->getPaginated(DocumentPrefix::class, $itemsPerPage, $cursor)
            ->andWhere('entity.organisation = :organisation')
            ->setParameter('organisation', $organisation)
            ->getQuery()
            ->getResult();
    }
}
