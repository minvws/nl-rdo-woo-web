<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Dossier;
use App\Entity\Organisation;
use App\Enum\PublicationStatus;
use App\ViewModel\DossierCounts;
use App\ViewModel\DossierReference;
use App\ViewModel\DossierSearchEntry;
use App\ViewModel\RecentDossier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Dossier>
 *
 * @method Dossier|null find($id, $lockMode = null, $lockVersion = null)
 * @method Dossier|null findOneBy(array $criteria, array $orderBy = null)
 * @method Dossier[]    findAll()
 * @method Dossier[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DossierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dossier::class);
    }

    public function save(Dossier $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Dossier $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Dossier[]
     */
    public function findBySearchTerm(string $searchTerm, int $limit, Organisation $organisation): array
    {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.inquiries', 'i')
            ->where('ILIKE(d.title, :searchTerm) = true')
            ->orWhere('ILIKE(d.dossierNr, :searchTerm) = true')
            ->orWhere('ILIKE(i.casenr, :searchTerm) = true')
            ->andWhere('d.organisation = :organisation')
            ->orderBy('d.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->setParameter('organisation', $organisation)
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Dossier[]
     */
    public function findPendingPreviewDossiers(\DateTimeImmutable $date): array
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.status = :status')
            ->andWhere('d.completed = true')
            ->andWhere('d.previewDate <= :date')
            ->setParameter('status', PublicationStatus::SCHEDULED)
            ->setParameter('date', $date);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Dossier[]
     */
    public function findPendingPublishDossiers(\DateTimeImmutable $date): array
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.status IN (:statuses)')
            ->andWhere('d.completed = true')
            ->andWhere('d.publicationDate <= :date')
            ->setParameter('statuses', [
                PublicationStatus::PREVIEW,
                PublicationStatus::SCHEDULED,
            ])
            ->setParameter('date', $date);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Dossier[]
     */
    public function findAllForOrganisation(Organisation $organisation): array
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.organisation = :organisation')
            ->setParameter('organisation', $organisation)
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @param PublicationStatus[] $statuses
     */
    public function getDossiersForOrganisationQueryBuilder(Organisation $organisation, array $statuses): QueryBuilder
    {
        return $this->createQueryBuilder('dos')
            ->leftJoin('dos.inquiries', 'inq')
            ->addSelect('inq')
            ->andWhere('dos.organisation = :organisation')
            ->andWhere('dos.status IN (:statuses)')
            ->setParameter('organisation', $organisation)
            ->setParameter('statuses', $statuses);
    }

    /**
     * @return RecentDossier[]
     */
    public function getRecentDossiers(int $limit): array
    {
        $qb = $this->createQueryBuilder('dos')
            ->select(sprintf(
                'new %s(
                    dos.dossierNr,
                    dos.documentPrefix,
                    dos.title,
                    dos.publicationDate,
                    dos.decisionDate,
                    COUNT(doc),
                    COALESCE(SUM(doc.pageCount),0)
                )',
                RecentDossier::class,
            ))
            ->where('dos.status = :status')
            ->leftJoin('dos.documents', 'doc')
            ->groupBy('dos.id')
            ->setParameter('status', PublicationStatus::PUBLISHED)
            ->orderBy('dos.decisionDate', 'DESC')
            ->setMaxResults($limit)
        ;

        return $qb->getQuery()->getResult();
    }

    public function getDossierSearchEntry(string $prefix, string $dossierNr): ?DossierSearchEntry
    {
        $qb = $this->createQueryBuilder('dos')
            ->select(sprintf(
                'new %s(
                    dos.dossierNr,
                    dos.documentPrefix,
                    dos.title, dos.decision,
                    dos.summary,
                    dos.publicationDate,
                    dos.decisionDate,
                    COUNT(doc)
                )',
                DossierSearchEntry::class,
            ))
            ->where('dos.documentPrefix = :prefix')
            ->andWhere('dos.dossierNr = :dossierNr')
            ->andWhere('dos.status IN (:statuses)')
            ->leftJoin('dos.documents', 'doc')
            ->groupBy('dos.id')
            ->setParameter('prefix', $prefix)
            ->setParameter('dossierNr', $dossierNr)
            ->setParameter('statuses', [PublicationStatus::PREVIEW, PublicationStatus::PUBLISHED])
        ;

        /** @var ?DossierSearchEntry $result */
        $result = $qb->getQuery()->getOneOrNullResult();

        return $result;
    }

    /**
     * @return DossierReference[]
     */
    public function getDossierReferencesForDocument(string $documentNr): array
    {
        $qb = $this->createQueryBuilder('dos')
            ->select(sprintf(
                'new %s(dos.dossierNr, dos.documentPrefix, dos.title)',
                DossierReference::class,
            ))
            ->where('doc.documentNr = :documentNr')
            ->andWhere('dos.status IN (:statuses)')
            ->innerJoin('dos.documents', 'doc')
            ->setParameter('documentNr', $documentNr)
            ->setParameter('statuses', [PublicationStatus::PREVIEW, PublicationStatus::PUBLISHED])
        ;

        return $qb->getQuery()->getResult();
    }

    public function getDossierCounts(Dossier $dossier): DossierCounts
    {
        $qb = $this->createQueryBuilder('dos')
            ->select(sprintf(
                'new %s(
                    COUNT(doc),
                    COALESCE(SUM(doc.pageCount),0),
                    SUM(CASE WHEN doc.fileInfo.uploaded = true THEN 1 ELSE 0 END)
                )',
                DossierCounts::class,
            ))
            ->where('dos = :dossier')
            ->leftJoin('dos.documents', 'doc')
            ->groupBy('dos.id')
            ->setParameter('dossier', $dossier)
        ;

        /** @var DossierCounts */
        return $qb->getQuery()->getSingleResult();
    }
}
