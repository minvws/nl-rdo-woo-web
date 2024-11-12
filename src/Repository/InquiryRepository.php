<?php

declare(strict_types=1);

namespace App\Repository;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\Inquiry;
use App\Entity\Judgement;
use App\Entity\Organisation;
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
    public function findByDossier(Dossier $dossier): array
    {
        return $this->createQueryBuilder('i')
            ->join('i.dossiers', 'd')
            ->andWhere('d.id = :dossierId')
            ->setParameter('dossierId', $dossier->getId())
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, array{title: string, dossierNr: string, doccount: int}>
     */
    public function getDocCountsByDossier(Inquiry $inquiry): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('dos.dossierNr', 'dos.title')
            ->addSelect('count(doc) as doccount')
            ->from(Dossier::class, 'dos')
            ->where('dos.status IN (:statuses)')
            ->innerJoin('dos.inquiries', 'inq', Join::WITH, 'inq.id = :inquiryId')
            ->innerJoin('dos.documents', 'doc')
            ->innerJoin('doc.inquiries', 'doc_inq', Join::WITH, 'doc_inq.id = :inquiryId')
            ->groupBy('dos.id')
            ->setParameter('inquiryId', $inquiry->getId())
            ->setParameter('statuses', [
                DossierStatus::PREVIEW,
                DossierStatus::PUBLISHED,
            ])
            ->getQuery()
            ->getArrayResult()
        ;
    }

    public function getQueryWithDocCountAndDossierCount(Organisation $organisation): Query
    {
        return $this->createQueryBuilder('inq')
            ->select('inq as inquiry')
            ->addSelect('inv')
            ->addSelect('count(distinct(doc.id)) as documentCount')
            ->addSelect('count(distinct(dos.id)) as dossierCount')
            ->where('inq.organisation = :organisation')
            ->leftJoin('inq.dossiers', 'dos')
            ->leftJoin('inq.documents', 'doc')
            ->leftJoin('inq.inventory', 'inv')
            ->groupBy('inq.id, inv.id')
            ->orderBy('inq.updatedAt', 'DESC')
            ->setParameter('organisation', $organisation)
            ->getQuery()
        ;
    }

    public function getDocsForInquiryDossierQueryBuilder(Inquiry $inquiry, Dossier $dossier): QueryBuilder
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
                ->addSelect('SUM(CASE WHEN doc.judgement = :' . $judgement->value . ' THEN 1 ELSE 0 END) as ' . $judgement->value)
                ->setParameter($judgement->value, $judgement->value);
        }

        /** @var array<string, int> $result */
        $result = $queryBuilder->getQuery()->getSingleResult();

        return $result;
    }

    public function countDocumentsForPubliclyAvailableDossiers(Inquiry $inquiry): int
    {
        return intval($this->createQueryBuilder('inq')
            ->select('count(doc)')
            ->join('inq.documents', 'doc')
            ->join('doc.dossiers', 'dos')
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
}
