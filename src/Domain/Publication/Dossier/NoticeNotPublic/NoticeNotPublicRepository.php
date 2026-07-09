<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\NoticeNotPublic;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

/**
 * @extends ServiceEntityRepository<NoticeNotPublic>
 */
class NoticeNotPublicRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NoticeNotPublic::class);
    }

    public function findOneByDossierId(Uuid $dossierId): ?NoticeNotPublic
    {
        $result = $this->createQueryBuilder('notice_not_public')
            ->where('notice_not_public.dossier = :dossierId')
            ->setParameter('dossierId', $dossierId)
            ->getQuery()
            ->getOneOrNullResult();

        Assert::nullOrIsInstanceOf($result, NoticeNotPublic::class);

        return $result;
    }

    public function save(NoticeNotPublic $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(NoticeNotPublic $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
