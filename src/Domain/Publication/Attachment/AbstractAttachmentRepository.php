<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment;

use App\Entity\Organisation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<AbstractAttachment>
 */
class AbstractAttachmentRepository extends ServiceEntityRepository
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

    /**
     * @return list<AbstractAttachment>
     */
    public function findBySearchTerm(string $searchTerm, int $limit, Organisation $organisation): array
    {
        $qb = $this->createQueryBuilder('a')
            ->join('a.dossier', 'd')
            ->where('ILIKE(a.fileInfo.name, :searchTerm) = true')
            ->andWhere('d.organisation = :organisation')
            ->orderBy('a.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->setParameter('organisation', $organisation);

        /** @var list<AbstractAttachment> */
        return $qb->getQuery()->getResult();
    }
}
