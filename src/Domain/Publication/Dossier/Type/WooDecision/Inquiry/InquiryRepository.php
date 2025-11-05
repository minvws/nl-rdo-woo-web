<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Inquiry;

use App\Domain\Organisation\Organisation;
use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Inquiry>
 */
class InquiryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Inquiry::class);
    }

    public function save(Inquiry $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Inquiry $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Inquiry[]
     */
    public function findByDossier(WooDecision $dossier): array
    {
        return $this->createQueryBuilder('i')
            ->join('i.dossiers', 'd')
            ->andWhere('d.id = :dossierId')
            ->setParameter('dossierId', $dossier->getId())
            ->getQuery()
            ->getResult();
    }

    public function getQueryWithDocCountAndDossierCount(Organisation $organisation): Query
    {
        $documentCountDQL = sprintf(
            '(%s) AS documentCount',
            $this->getEntityManager()->createQueryBuilder()
                ->select('count(doc_sub.id)')
                ->from(Inquiry::class, 'inq_sub_doc')
                ->join('inq_sub_doc.documents', 'doc_sub')
                ->where('inq_sub_doc.id = inq.id')
                ->getDQL()
        );

        $dossierCountDQL = sprintf(
            '(%s) AS dossierCount',
            $this->getEntityManager()->createQueryBuilder()
                ->select('count(dos_sub.id)')
                ->from(Inquiry::class, 'inq_sub_dos')
                ->join('inq_sub_dos.dossiers', 'dos_sub')
                ->where('inq_sub_dos.id = inq.id')
                ->getDQL()
        );

        return $this->createQueryBuilder('inq')
            ->select('inq as inquiry')
            ->addSelect('inv')
            ->addSelect($documentCountDQL)
            ->addSelect($dossierCountDQL)
            ->leftJoin('inq.inventory', 'inv')
            ->where('inq.organisation = :organisation')
            ->orderBy('inq.updatedAt', 'DESC')
            ->setParameter('organisation', $organisation)
            ->getQuery()
        ;
    }

    public function getDocsForInquiryDossierQueryBuilder(Inquiry $inquiry, WooDecision $dossier): QueryBuilder
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('doc, dos')
            ->from(Document::class, 'doc')
            ->innerJoin('doc.inquiries', 'inq', Join::WITH, 'inq.id = :inquiryId')
            ->innerJoin('doc.dossiers', 'dos', Join::WITH, 'dos.id = :dossierId')
            ->where('dos.status IN (:statuses)')
            ->setParameter('inquiryId', $inquiry->getId())
            ->setParameter('dossierId', $dossier->getId())
            ->setParameter('statuses', [
                DossierStatus::PREVIEW,
                DossierStatus::PUBLISHED,
            ])
        ;
    }

    /**
     * @return array<string, int>
     */
    public function countDocumentsByJudgement(Inquiry $inquiry): array
    {
        $queryBuilder = $this->createQueryBuilder('inq')
            ->select('count(doc) as total')
            ->join('inq.documents', 'doc')
            ->join('doc.dossiers', 'dos')
            ->where('inq.id = :inquiryId')
            ->andWhere('dos.status IN (:statuses)')
            ->setParameter('inquiryId', $inquiry->getId())
            ->setParameter('statuses', [
                DossierStatus::PREVIEW,
                DossierStatus::PUBLISHED,
            ]);

        foreach (Judgement::cases() as $judgement) {
            $queryBuilder
                ->addSelect('SUM('
                    . 'CASE WHEN doc.judgement = :' . $judgement->value . ' THEN 1 ELSE 0 END'
                    . ') as ' . $judgement->value)
                ->setParameter($judgement->value, $judgement->value);

            if ($judgement->isAtLeastPartialPublic()) {
                $queryBuilder
                    ->addSelect('SUM('
                        . 'CASE WHEN doc.judgement = :' . $judgement->value . ' AND doc.withdrawn=true  THEN 1 ELSE 0 END'
                        . ') as ' . $judgement->value . '_withdrawn')
                    ->addSelect('SUM('
                        . ' CASE WHEN doc.judgement = :' . $judgement->value . ' AND doc.suspended=true THEN 1 ELSE 0 END'
                        . ') as ' . $judgement->value . '_suspended');
            }
        }

        /** @var array<string, int> */
        return $queryBuilder->getQuery()->getSingleResult();
    }

    public function getDocumentsForPubliclyAvailableDossiers(Inquiry $inquiry): QueryBuilder
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('doc, dos')
            ->from(Document::class, 'doc')
            ->join('doc.inquiries', 'inq', Join::WITH, 'inq.id = :inquiryId')
            ->join('doc.dossiers', 'dos')
            ->where('inq.id = :inquiryId')
            ->andWhere('dos.status IN (:statuses)')
            ->setParameter('inquiryId', $inquiry->getId())
            ->setParameter('statuses', [
                DossierStatus::PREVIEW,
                DossierStatus::PUBLISHED,
            ]);
    }

    public function getDocumentsForBatchDownload(Inquiry $inquiry, ?WooDecision $wooDecision = null): QueryBuilder
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('doc')
            ->from(Document::class, 'doc')
            ->join('doc.inquiries', 'inq', Join::WITH, 'inq.id = :inquiryId')
            ->join('doc.dossiers', 'dos')
            ->where('inq.id = :inquiryId')
            ->andWhere('dos.status IN (:statuses)')
            ->andWhere('doc.fileInfo.uploaded = true')
            ->andWhere('doc.suspended = false')
            ->andWhere('doc.withdrawn = false')
            ->andWhere('doc.judgement IN(:judgements)')
            ->setParameter('inquiryId', $inquiry->getId())
            ->setParameter('statuses', [
                DossierStatus::PREVIEW,
                DossierStatus::PUBLISHED,
            ])
            ->setParameter('judgements', Judgement::atLeastPartialPublicValues());

        if ($wooDecision instanceof WooDecision) {
            $queryBuilder->andWhere('dos.id = :dossierId');
            $queryBuilder->setParameter('dossierId', $wooDecision->getId());
        }

        return $queryBuilder;
    }

    public function countPubliclyAvailableDossiers(Inquiry $inquiry): int
    {
        return intval($this->createQueryBuilder('inq')
            ->select('count(dos)')
            ->join('inq.dossiers', 'dos')
            ->where('inq.id = :inquiryId')
            ->andWhere('dos.status IN (:statuses)')
            ->setParameter('inquiryId', $inquiry->getId())
            ->setParameter('statuses', [
                DossierStatus::PREVIEW,
                DossierStatus::PUBLISHED,
            ])
            ->getQuery()
            ->getSingleScalarResult());
    }

    public function getDossiersForInquiryQueryBuilder(Inquiry $inquiry): QueryBuilder
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('dos')
            ->addSelect('COUNT(doc) as docCount')
            ->from(WooDecision::class, 'dos')
            ->join('dos.inquiries', 'inq', Join::WITH, 'inq = :inquiry')
            ->leftJoin('dos.documents', 'doc', Join::WITH, 'inq MEMBER OF doc.inquiries')
            ->where('dos.status IN (:statuses)')
            ->setParameter('inquiry', $inquiry)
            ->setParameter('statuses', [
                DossierStatus::PREVIEW,
                DossierStatus::PUBLISHED,
            ])
            ->groupBy('dos.id');
    }
}
