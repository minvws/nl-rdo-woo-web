<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Document;
use App\Service\Elastic\Model\DocumentCounts;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

    //    /**
    //     * @return Document[] Returns an array of Document objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Document
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

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
            ->andWhere('d NOT INSTANCE OF App\Entity\Inventory')
            ->andWhere('d NOT INSTANCE OF App\Entity\Decision')
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

    //    /**
    //     * @return Document[]
    //     */
    //    public function findLatests(int $limit): array
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->orderBy('d.createdAt', 'DESC')
    //            ->setMaxResults($limit)
    //            ->getQuery()
    //            ->getResult();
    //    }

    //    public function findByDossierAndDocument(string $dossierId, string $documentId)
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere(':dossierId MEMBER OF d.dossiers')
    //            ->andWhere('d.id = :documentId')
    //            ->setParameter('dossierId', $dossierId)
    //            ->setParameter('documentId', $documentId)
    //            ->getQuery()
    //            ->getOneOrNullResult();
    //    }
}
