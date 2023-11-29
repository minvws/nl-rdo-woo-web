<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\Inquiry;
use App\Service\Elastic\Model\DocumentCounts;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
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
    public function findByThreadId(Dossier $dossier, int $threadId): array
    {
        $qb = $this->createQueryBuilder('d')
            ->innerJoin('d.dossiers', 'ds')
            ->where('d.threadId = :threadId')
            ->andWhere('ds = :dossier')
            ->andWhere('ds.status = :status')
            ->orderBy('d.documentDate', 'ASC')
            ->setParameter('threadId', $threadId)
            ->setParameter('dossier', $dossier)
            ->setParameter('status', Dossier::STATUS_PUBLISHED)
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Document[]
     */
    public function findByFamilyId(Dossier $dossier, int $familyId): array
    {
        $qb = $this->createQueryBuilder('d')
            ->innerJoin('d.dossiers', 'ds')
            ->where('d.familyId = :familyId')
            ->andWhere('ds = :dossier')
            ->andWhere('ds.status = :status')
            ->orderBy('d.documentDate', 'ASC')
            ->setParameter('familyId', $familyId)
            ->setParameter('dossier', $dossier)
            ->setParameter('status', Dossier::STATUS_PUBLISHED)
        ;

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

    public function getCountAndPageSum(): DocumentCounts
    {
        $queryBuilder = $this->createQueryBuilder('d')
            ->select('COUNT(DISTINCT d.id) as documentCount')
            ->addSelect('SUM(d.pageCount) as totalPageCount')
            ->innerJoin('d.dossiers', 'ds');

        /** @var array{documentCount: int, totalPageCount: int|null} $result */
        $result = $queryBuilder->getQuery()->getSingleResult();

        return new DocumentCounts(
            documentCount: $result['documentCount'],
            totalPageCount: (int) $result['totalPageCount'],
        );
    }

    public function getRelatedDocumentsByThread(Dossier $dossier, Document $document): ArrayCollection|QueryBuilder
    {
        $threadId = $document->getThreadId();
        if ($threadId < 1) {
            return new ArrayCollection();
        }

        return $this->createQueryBuilder('doc')
            ->select('doc, dos')
            ->innerJoin('doc.dossiers', 'dos')
            ->where('doc.threadId = :threadId')
            ->andWhere('dos = :dossier')
            ->andWhere('dos.status = :status')
            ->andWhere('doc != :document')
            ->orderBy('doc.documentDate', 'ASC')
            ->setParameter('threadId', $threadId)
            ->setParameter('dossier', $dossier)
            ->setParameter('document', $document)
            ->setParameter('status', Dossier::STATUS_PUBLISHED)
        ;
    }

    public function getRelatedDocumentsByFamily(Dossier $dossier, Document $document): ArrayCollection|QueryBuilder
    {
        $familyId = $document->getFamilyId();
        if ($familyId < 1) {
            return new ArrayCollection();
        }

        return $this->createQueryBuilder('doc')
            ->select('doc, dos')
            ->innerJoin('doc.dossiers', 'dos')
            ->where('doc.familyId = :familyId')
            ->andWhere('dos = :dossier')
            ->andWhere('dos.status = :status')
            ->andWhere('doc != :document')
            ->orderBy('doc.documentDate', 'ASC')
            ->setParameter('familyId', $familyId)
            ->setParameter('dossier', $dossier)
            ->setParameter('document', $document)
            ->setParameter('status', Dossier::STATUS_PUBLISHED)
        ;
    }

    /**
     * @param string[]|null $prefixFilter
     *
     * @return Document[]
     */
    public function findBySearchTerm(string $searchTerm, int $limit, ?array $prefixFilter): array
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

        if ($prefixFilter) {
            $qb->andWhere('ds.documentPrefix IN (:prefixes)')->setParameter('prefixes', $prefixFilter);
        }

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

    public function findOneByDossierNrAndDocumentNr(string $dossierNr, string $documentNr): ?Document
    {
        $qb = $this->createQueryBuilder('d')
            ->innerJoin('d.dossiers', 'ds')
            ->where('d.documentNr = :documentNr')
            ->andWhere('ds.dossierNr = :dossierNr')
            ->setParameter('documentNr', $documentNr)
            ->setParameter('dossierNr', $dossierNr)
        ;

        /** @var ?Document $document */
        $document = $qb->getQuery()->getOneOrNullResult();

        return $document;
    }

    public function getDossierDocumentsQueryBuilder(Dossier $dossier): QueryBuilder
    {
        return $this->createQueryBuilder('doc')
            ->innerJoin('doc.dossiers', 'dos')
            ->where('dos.id = :dossierId')
            ->setParameter('dossierId', $dossier->getId())
        ;
    }

    /**
     * @return Document[]
     */
    public function findForDossierBySearchTerm(Dossier $dossier, string $searchTerm, int $limit): array
    {
        $qb = $this->createQueryBuilder('d')
            ->innerJoin('d.dossiers', 'ds', Join::WITH, 'ds.id = :dossierId')
            ->where('d.fileInfo.name LIKE :searchTerm')
            ->orWhere('d.documentNr LIKE :searchTerm')
            ->orderBy('d.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->setParameter('dossierId', $dossier->getId())
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Document[]
     */
    public function getAllDossierDocumentsWithDossiers(Dossier $dossier): array
    {
        $qb = $this->getDossierDocumentsQueryBuilder($dossier)
            ->select('doc', 'dos');

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Document[]
     */
    public function getAllInquiryDocumentsWithDossiers(Inquiry $inquiry): array
    {
        $qb = $this->createQueryBuilder('doc')
            ->select('doc', 'dos')
            ->innerJoin('doc.dossiers', 'dos')
            ->innerJoin('doc.inquiries', 'doc_inq')
            ->where('doc_inq.id = :inquiryId')
            ->setParameter('inquiryId', $inquiry->getId())
        ;

        return $qb->getQuery()->getResult();
    }
}
