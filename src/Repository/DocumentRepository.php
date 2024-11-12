<?php

declare(strict_types=1);

namespace App\Repository;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Search\Index\Rollover\DocumentCounts;
use App\Domain\Search\Result\SubType\WooDecisionDocument\DocumentViewModel;
use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\Inquiry;
use App\Entity\Organisation;
use App\Service\Inventory\DocumentNumber;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 *
 * @extends ServiceEntityRepository<Document>
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
            ->setParameter('status', DossierStatus::PUBLISHED)
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
            ->setParameter('status', DossierStatus::PUBLISHED)
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
            ->setParameter('status', DossierStatus::PUBLISHED)
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
            ->setParameter('status', DossierStatus::PUBLISHED)
        ;
    }

    /**
     * @return Document[]
     */
    public function findBySearchTerm(string $searchTerm, int $limit, Organisation $organisation): array
    {
        $qb = $this->createQueryBuilder('d')
            ->innerJoin('d.dossiers', 'ds')
            ->leftJoin('d.inquiries', 'i')
            ->where('ILIKE(d.fileInfo.name, :searchTerm) = true')
            ->orWhere('ILIKE(d.documentNr, :searchTerm) = true')
            ->orWhere('ILIKE(i.casenr, :searchTerm) = true')
            ->andWhere('ds.organisation = :organisation')
            ->orderBy('d.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->setParameter('organisation', $organisation)
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

    public function findOneByDossierNrAndDocumentNr(string $prefix, string $dossierNr, string $documentNr): ?Document
    {
        $qb = $this->createQueryBuilder('d')
            ->innerJoin('d.dossiers', 'ds')
            ->where('d.documentNr = :documentNr')
            ->andWhere('ds.dossierNr = :dossierNr')
            ->andWhere('ds.documentPrefix = :prefix')
            ->setParameter('documentNr', $documentNr)
            ->setParameter('dossierNr', $dossierNr)
            ->setParameter('prefix', $prefix)
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
            ->where('ILIKE(d.fileInfo.name, :searchTerm) = true')
            ->orWhere('ILIKE(d.documentNr, :searchTerm) = true')
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

    /**
     * @return string[]
     */
    public function getAllDocumentNumbersForDossier(Dossier $dossier): array
    {
        /** @var string[] $docNumbers */
        $docNumbers = $this->getDossierDocumentsQueryBuilder($dossier)
            ->select('doc.documentNr')
            ->getQuery()
            ->getSingleColumnResult();

        return $docNumbers;
    }

    public function findByDocumentNumber(DocumentNumber $documentNumber): ?Document
    {
        return $this->findOneBy(['documentNr' => $documentNumber->getValue()]);
    }

    /**
     * @return Document[]
     */
    public function getPublishedDocuments(int $limit = 50000, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('d')
            ->innerJoin('d.dossiers', 'ds')
            ->where('ds.status = :status')
            ->orderBy('d.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->setParameter('status', DossierStatus::PUBLISHED);

        return $qb->getQuery()->getResult();
    }

    public function getDocumentSearchEntry(string $documentNr): ?DocumentViewModel
    {
        $qb = $this->createQueryBuilder('doc')
            ->select(sprintf(
                'new %s(
                    doc.documentId,
                    doc.documentNr,
                    doc.fileInfo.name,
                    doc.fileInfo.sourceType,
                    doc.fileInfo.uploaded,
                    doc.fileInfo.size,
                    doc.pageCount,
                    doc.judgement,
                    doc.documentDate
                )',
                DocumentViewModel::class,
            ))
            ->where('doc.documentNr = :documentNr')
            ->andWhere('dos.status IN (:statuses)')
            ->innerJoin('doc.dossiers', 'dos')
            ->groupBy('doc.id')
            ->setParameter('documentNr', $documentNr)
            ->setParameter('statuses', [DossierStatus::PREVIEW, DossierStatus::PUBLISHED])
        ;

        /** @var ?DocumentViewModel $result */
        $result = $qb->getQuery()->getOneOrNullResult();

        return $result;
    }

    /**
     * @return Document[]
     */
    public function getRevokedDocumentsInPublicDossiers(): array
    {
        $qb = $this->createQueryBuilder('d')
            ->innerJoin('d.dossiers', 'ds')
            ->where('ds.status = :status')
            ->andWhere('d.withdrawn = true OR d.suspended = true')
            ->setParameter('status', DossierStatus::PUBLISHED);

        return $qb->getQuery()->getResult();
    }
}
