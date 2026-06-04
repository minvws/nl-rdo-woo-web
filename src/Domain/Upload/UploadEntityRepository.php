<?php

declare(strict_types=1);

namespace Shared\Domain\Upload;

use Carbon\CarbonImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Shared\Service\Security\User;
use Shared\Service\Uploader\UploadGroupId;
use Symfony\Component\HttpFoundation\InputBag;
use Webmozart\Assert\Assert;

/**
 * @extends ServiceEntityRepository<UploadEntity>
 */
class UploadEntityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UploadEntity::class);
    }

    public function save(UploadEntity $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(UploadEntity $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOrCreate(string $uploadId, UploadGroupId $groupId, ?User $user, InputBag $context): UploadEntity
    {
        $entity = $this->findOneBy(['uploadId' => $uploadId]);

        if (! $entity) {
            $entity = new UploadEntity($uploadId, $groupId, $user, $context);
            $this->save($entity, true);
        }

        return $entity;
    }

    /**
     * @return array<array-key, UploadEntity>
     */
    public function findUploadsForCleanup(CarbonImmutable $maxUpdatedAt): array
    {
        $qb = $this->createQueryBuilder('u')
            ->where('u.updatedAt < :maxUpdatedAt')
            ->orWhere('u.status = :stored')
            ->setParameter('maxUpdatedAt', $maxUpdatedAt)
            ->setParameter('stored', UploadEntityStatus::STORED);

        return $qb->getQuery()->getResult();
    }

    public function findLatestByContextData(string $key, string $value): ?UploadEntity
    {
        $uploadEntity = $this->createQueryBuilder('uploadEntity')
            ->where('json_extract_path_text(uploadEntity.context, :key) = :value')
            ->setParameter('key', $key)
            ->setParameter('value', $value)
            ->orderBy('uploadEntity.createdAt', 'desc')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        Assert::nullOrIsInstanceOf($uploadEntity, UploadEntity::class);

        return $uploadEntity;
    }
}
