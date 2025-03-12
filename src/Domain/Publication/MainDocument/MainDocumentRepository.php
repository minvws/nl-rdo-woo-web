<?php

declare(strict_types=1);

namespace App\Domain\Publication\MainDocument;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Entity\Organisation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<AbstractMainDocument>
 */
class MainDocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AbstractMainDocument::class);
    }

    /**
     * @return list<AbstractMainDocument>
     */
    public function findBySearchTerm(
        string $searchTerm,
        int $limit,
        Organisation $organisation,
        ?Uuid $dossierId = null,
        ?DossierType $dossierType = null,
    ): array {
        $qb = $this
            ->createQueryBuilder('md')
            ->join('md.dossier', 'd')
            ->andWhere('ILIKE(md.fileInfo.name, :searchTerm) = true')
            ->andWhere('d.organisation = :organisation')
            ->orderBy('md.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->setParameter('organisation', $organisation);

        if ($dossierId !== null) {
            $qb = $qb
                ->andWhere('d.id = :dossierId')
                ->setParameter('dossierId', $dossierId);
        }

        if ($dossierType !== null) {
            $qb = $qb
                ->andWhere('d INSTANCE OF :dossierType')
                ->setParameter('dossierType', $dossierType);
        }

        /** @var list<AbstractMainDocument> */
        return $qb->getQuery()->getResult();
    }

    public function findOneOrNullForDossier(Uuid $dossierId, Uuid $id): ?AbstractMainDocument
    {
        $qb = $this->createQueryBuilder('md')
            ->where('md.id = :id')
            ->andWhere('dos.id = :dossierId')
            ->innerJoin('md.dossier', 'dos')
            ->setParameter('id', $id)
            ->setParameter('dossierId', $dossierId)
        ;

        /** @var ?AbstractMainDocument */
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getAllPublishedQuery(): Query
    {
        return $this
            ->createQueryBuilder('md')
            ->join('md.dossier', 'd')
            ->where('d.status = :status')
            ->setParameter('status', DossierStatus::PUBLISHED)
            ->getQuery();
    }

    /**
     * @return iterable<int,AbstractMainDocument>
     */
    public function getPublishedMainDocumentsIterable(): iterable
    {
        $qb = $this->createQueryBuilder('md')
            ->join('md.dossier', 'd')
            ->where('d.status = :status')
            ->orderBy('md.createdAt', 'ASC')
            ->setParameter('status', DossierStatus::PUBLISHED);

        return $qb->getQuery()->toIterable();
    }
}
