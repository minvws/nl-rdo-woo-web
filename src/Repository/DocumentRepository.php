<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Service\Elastic\Model\DocumentCounts;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Document>
 *
 * @method Document|null find($id, $lockMode = null, $lockVersion = null)
 * @method Document|null findOneBy(array $criteria, array $orderBy = null)
 * @method Document[]    findAll()
 * @method Document[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Document::class);
    }

    public function save(Document $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Document $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Document[]
     */
    public function findByThreadId(int $threadId, bool $onlyPublished = true): array
    {
        $qb = $this->createQueryBuilder('d')
            ->innerJoin('d.dossiers', 'ds')
            ->where('d.threadId = :threadId')
            ->orderBy('d.documentDate', 'ASC')
            ->setParameter('threadId', $threadId)
        ;

        if ($onlyPublished) {
            $qb
                ->andWhere('ds.status = :status')
                ->setParameter('status', Dossier::STATUS_PUBLISHED)
            ;
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Document[]
     */
    public function findByFamilyId(int $familyId, bool $onlyPublished = true): array
    {
        $qb = $this->createQueryBuilder('d')
            ->innerJoin('d.dossiers', 'ds')
            ->where('d.familyId = :familyId')
            ->orderBy('d.documentDate', 'ASC')
            ->setParameter('familyId', $familyId)
        ;

        if ($onlyPublished) {
            $qb
                ->andWhere('ds.status = :status')
                ->setParameter('status', Dossier::STATUS_PUBLISHED)
            ;
        }

        return $qb->getQuery()->getResult();
    }

    public function pagecount(): int
    {
        $result = $this->createqueryBuilder('d')
            ->select('sum(d.pageCount)')
            ->getQuery()
            ->getSingleScalarResult();

        return intval($result);
    }

    /**
     * @param string[] $dossierStatuses
     */
    public function getCountAndPageSumForStatuses(array $dossierStatuses = []): DocumentCounts
    {
        $qb = $this->createQueryBuilder('d');
        $qb
            ->select('COUNT(DISTINCT d.id) as documentCount')
            ->addSelect('SUM(d.pageCount) as totalPageCount')
            ->innerJoin('d.dossiers', 'ds')
            ->where($qb->expr()->in('ds.status', ':statuses'))
            ->setParameters([
                'statuses' => $dossierStatuses,
            ]);

        /** @var array{documentCount: int, totalPageCount: int|null} $result */
        $result = $qb->getQuery()->getSingleResult();

        return new DocumentCounts(
            documentCount: $result['documentCount'],
            totalPageCount: (int) $result['totalPageCount'],
        );
    }

    public function getRelatedDocumentsByThread(Document $document): ArrayCollection
    {
        $threadId = $document->getThreadId();
        if ($threadId < 1) {
            return new ArrayCollection();
        }

        $threadDocuments = new ArrayCollection(
            $this->findByThreadId($threadId)
        );

        return $threadDocuments->filter(
            fn (Document $threadDocument): bool => $threadDocument->getId() !== $document->getId()
        );
    }

    public function getRelatedDocumentsByFamily(Document $document): ArrayCollection
    {
        $familyId = $document->getFamilyId();
        if ($familyId < 1) {
            return new ArrayCollection();
        }

        $familyDocuments = new ArrayCollection(
            $this->findByFamilyId($familyId)
        );

        return $familyDocuments->filter(
            fn (Document $familyDocument): bool => $familyDocument->getId() !== $document->getId()
        );
    }

    /**
     * @return Document[]
     */
    public function findBySearchTerm(string $searchTerm, int $limit): array
    {
        $qb = $this->createQueryBuilder('d')
            ->innerJoin('d.dossiers', 'ds')
            ->leftJoin('d.inquiries', 'i')
            ->where('d.fileInfo.name LIKE :searchTerm')
            ->orWhere('d.documentNr LIKE :searchTerm')
            ->orWhere('i.casenr LIKE :searchTerm')
            ->orderBy('d.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
        ;

        return $qb->getQuery()->getResult();
    }

    public function findOneByDossierAndDocumentId(Dossier $dossier, string $documentId): ?Document
    {
        $qb = $this->createQueryBuilder('d')
            ->innerJoin('d.dossiers', 'ds')
            ->where('d.documentId = :documentId')
            ->andWhere('ds.id = :dossierId')
            ->setParameter('documentId', $documentId)
            ->setParameter('dossierId', $dossier->getId())
        ;

        /** @var ?Document $document */
        $document = $qb->getQuery()->getOneOrNullResult();

        return $document;
    }
}
