<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment\Repository;

use App\Domain\Publication\Attachment\Entity\AbstractAttachment;
use App\Domain\Publication\Dossier\DossierStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<AbstractAttachment>
 */
class AttachmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AbstractAttachment::class);
    }

    public function save(AbstractAttachment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AbstractAttachment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneOrNullForDossier(Uuid $dossierId, Uuid $id): ?AbstractAttachment
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.id = :id')
            ->andWhere('dos.id = :dossierId')
            ->innerJoin('a.dossier', 'dos')
            ->setParameter('id', $id)
            ->setParameter('dossierId', $dossierId)
        ;

        /** @var ?AbstractAttachment */
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getAllPublishedQuery(): Query
    {
        return $this
            ->createQueryBuilder('a')
            ->join('a.dossier', 'd')
            ->where('d.status = :status')
            ->setParameter('status', DossierStatus::PUBLISHED)
            ->getQuery();
    }

    /**
     * @return iterable<int,AbstractAttachment>
     */
    public function getPublishedAttachmentsIterable(): iterable
    {
        $qb = $this->createQueryBuilder('a')
            ->join('a.dossier', 'd')
            ->where('d.status = :status')
            ->andWhere('a.fileInfo.uploaded = true')
            ->orderBy('a.createdAt', 'ASC')
            ->setParameter('status', DossierStatus::PUBLISHED);

        return $qb->getQuery()->toIterable();
    }
}
