<?php

declare(strict_types=1);

namespace App\Domain\Publication\MainDocument;

use App\Domain\Publication\Attachment\AbstractAttachment;
use App\Entity\Organisation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AbstractAttachment>
 */
class AbstractMainDocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AbstractMainDocument::class);
    }

    /**
     * @return list<AbstractMainDocument>
     */
    public function findBySearchTerm(string $searchTerm, int $limit, Organisation $organisation): array
    {
        $qb = $this->createQueryBuilder('md')
            ->join('md.dossier', 'd')
            ->andWhere('ILIKE(md.fileInfo.name, :searchTerm) = true')
            ->andWhere('d.organisation = :organisation')
            ->orderBy('md.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->setParameter('organisation', $organisation);

        /** @var list<AbstractMainDocument> */
        return $qb->getQuery()->getResult();
    }
}
