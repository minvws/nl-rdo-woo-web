<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\MainDocument;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Shared\Domain\Publication\Dossier\DossierStatus;
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

    public function findOneOrNullForDossier(Uuid $dossierId, Uuid $id): ?AbstractMainDocument
    {
        $qb = $this->createQueryBuilder('md')
            ->where('md.id = :id')
            ->andWhere('dos.id = :dossierId')
            ->innerJoin('md.dossier', 'dos')
            ->setParameter('id', $id)
            ->setParameter('dossierId', $dossierId);

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
            ->andWhere('md.fileInfo.uploaded = true')
            ->orderBy('md.createdAt', 'ASC')
            ->setParameter('status', DossierStatus::PUBLISHED);

        return $qb->getQuery()->toIterable();
    }
}
