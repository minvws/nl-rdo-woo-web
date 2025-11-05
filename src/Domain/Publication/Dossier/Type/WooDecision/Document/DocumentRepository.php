<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Document;

use App\Doctrine\SortNullsLastWalker;
use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use App\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Search\Result\SubType\WooDecisionDocument\DocumentViewModel;
use App\Service\Inquiry\DocumentCaseNumbers;
use App\Service\Inventory\DocumentNumber;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @SuppressWarnings("PHPMD.TooManyPublicMethods")
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
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
    public function findByThreadId(WooDecision $dossier, int $threadId): array
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
    public function findByFamilyId(WooDecision $dossier, int $familyId): array
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
            ->select('sum(d.fileInfo.pageCount)')
            ->getQuery()
            ->getSingleScalarResult();

        return intval($result);
    }

    public function getRelatedDocumentsByThread(WooDecision $dossier, Document $document): ArrayCollection|QueryBuilder
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

    public function getRelatedDocumentsByFamily(WooDecision $dossier, Document $document): ArrayCollection|QueryBuilder
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

    public function findOneByDossierAndDocumentId(WooDecision $dossier, string $documentId): ?Document
    {
        $qb = $this->createQueryBuilder('d')
            ->innerJoin('d.dossiers', 'ds')
            ->where('d.documentId = :documentId')
            ->andWhere('ds.id = :dossierId')
            ->setParameter('documentId', $documentId)
            ->setParameter('dossierId', $dossier->getId())
        ;

        /** @var ?Document */
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findOneByDossierAndId(WooDecision $dossier, Uuid $id): ?Document
    {
        $qb = $this->createQueryBuilder('d')
            ->innerJoin('d.dossiers', 'ds')
            ->where('d.id = :id')
            ->andWhere('ds.id = :dossierId')
            ->setParameter('id', $id)
            ->setParameter('dossierId', $dossier->getId())
        ;

        /** @var ?Document */
        return $qb->getQuery()->getOneOrNullResult();
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

        /** @var ?Document */
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getDossierDocumentsQueryBuilder(WooDecision $dossier): QueryBuilder
    {
        return $this->createQueryBuilder('doc')
            ->innerJoin('doc.dossiers', 'dos')
            ->where('dos.id = :dossierId')
            ->setParameter('dossierId', $dossier->getId())
        ;
    }

    public function getDossierDocumentsForPaginationQuery(WooDecision $dossier): Query
    {
        return $this->getDossierDocumentsQueryBuilder($dossier)
            ->addSelect('
                (CASE
                    WHEN doc.withdrawn=true THEN 1
                    WHEN doc.suspended=true THEN 3
                    WHEN doc.judgement IN (:publicJudgements) AND doc.fileInfo.uploaded=false THEN 2
                    ELSE NULLIF(1,1)
                END) AS HIDDEN hasNotice')
            ->setParameter('publicJudgements', Judgement::atLeastPartialPublicValues())
            ->orderBy('doc.documentDate', 'ASC')
            ->getQuery()
            ->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, SortNullsLastWalker::class);
    }

    /**
     * @return Document[]
     */
    public function findForDossierBySearchTerm(WooDecision $dossier, string $searchTerm, int $limit): array
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
    public function getAllDossierDocumentsWithDossiers(WooDecision $dossier): array
    {
        $qb = $this->getDossierDocumentsQueryBuilder($dossier)
            ->select('doc', 'dos');

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Document[]
     */
    public function getPublicInquiryDocumentsWithDossiers(Inquiry $inquiry): array
    {
        $qb = $this->createQueryBuilder('doc')
            ->select('doc', 'dos')
            ->innerJoin('doc.dossiers', 'dos')
            ->innerJoin('doc.inquiries', 'doc_inq')
            ->where('doc_inq.id = :inquiryId')
            ->andWhere('dos.status IN (:statuses)')
            ->setParameter('inquiryId', $inquiry->getId())
            ->setParameter('statuses', DossierStatus::publiclyAvailableCases())
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @return string[]
     */
    public function getAllDocumentNumbersForDossier(WooDecision $dossier): array
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
     * @return iterable<int,Document>
     */
    public function getPublishedDocumentsIterable(): iterable
    {
        $qb = $this->createQueryBuilder('d')
            ->where('EXISTS (
                SELECT 1
                FROM App\Domain\Publication\Dossier\Type\WooDecision\WooDecision ds
                WHERE ds MEMBER OF d.dossiers AND ds.status = :status
            )')
            ->andWhere('d.judgement IN (:judgements)')
            ->andWhere('d.fileInfo.uploaded = true')
            ->orderBy('d.createdAt', 'ASC')
            ->setParameter('status', DossierStatus::PUBLISHED)
            ->setParameter('judgements', [Judgement::PUBLIC, Judgement::PARTIAL_PUBLIC]);

        return $qb->getQuery()->toIterable();
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
                    doc.fileInfo.pageCount,
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

        /** @var ?DocumentViewModel */
        return $qb->getQuery()->getOneOrNullResult();
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

    public function findOneByDocumentNrCaseInsensitive(string $documentNr): ?Document
    {
        $qb = $this->createQueryBuilder('d')
            ->where('LOWER(d.documentNr) = LOWER(:documentNr)')
            ->setParameter('documentNr', $documentNr)
        ;

        /** @var ?Document */
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getDocumentCaseNrs(string $documentNr): DocumentCaseNumbers
    {
        /**
         * @var array<array-key, array{id:Uuid, casenr:string}> $result
         */
        $result = $this->createQueryBuilder('d')
            ->select('d.id, inq.casenr')
            ->where('LOWER(d.documentNr) = LOWER(:documentNr)')
            ->setParameter('documentNr', $documentNr)
            ->leftJoin('d.inquiries', 'inq')
            ->getQuery()
            ->getArrayResult();

        return DocumentCaseNumbers::fromArray($result);
    }
}
