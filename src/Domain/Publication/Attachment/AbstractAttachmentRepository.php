<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<AbstractAttachment>
 *
 * @method AbstractAttachment|null find($id, $lockMode = null, $lockVersion = null)
 * @method AbstractAttachment|null findOneBy(array $criteria, array $orderBy = null)
 * @method AbstractAttachment[]    findAll()
 * @method AbstractAttachment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
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
}
