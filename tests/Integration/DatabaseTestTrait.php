<?php

declare(strict_types=1);

namespace Shared\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Webmozart\Assert\Assert;

use function is_array;
use function PHPUnit\Framework\assertEquals;
use function sprintf;

trait DatabaseTestTrait
{
    /**
     * @param class-string $entityClass
     */
    public static function assertDatabaseCount(string $entityClass, int $count): void
    {
        self::getEntityManager()->flush();

        self:assertEquals($count, self::getRepository($entityClass)->count());
    }

    /**
     * example for $criteria: [
     *     'column' => 'my-value',
     *     'my-relation' => ['my-relation-column' => 'my-value'],
     * ].
     *
     * @param class-string $entityClass
     * @param array<string,mixed|array<string,mixed>> $criteria
     */
    public static function assertDatabaseHas(string $entityClass, array $criteria): void
    {
        self::assertGreaterThanOrEqual(1, self::getResultCount(self::getRepository($entityClass), $criteria));
    }

    /**
     * example for $criteria: [
     *     'column' => 'my-value',
     *     'my-relation' => ['my-relation-column' => 'my-value'],
     * ].
     *
     * @param class-string $entityClass
     * @param array<string,mixed|array<string,mixed>> $criteria
     */
    public static function assertDatabaseMissing(string $entityClass, array $criteria): void
    {
        self::assertGreaterThanOrEqual(0, self::getResultCount(self::getRepository($entityClass), $criteria));
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $entityClass
     * @param array<string,mixed> $criteria
     *
     * @return T|null
     */
    public function getEntity(string $entityClass, array $criteria): ?object
    {
        $repository = self::getRepository($entityClass);

        return $repository->findOneBy($criteria);
    }

    private static function getEntityManager(): EntityManagerInterface
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        Assert::isInstanceOf($entityManager, EntityManagerInterface::class);

        return $entityManager;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $entityClass
     *
     * @return EntityRepository<T>
     */
    private static function getRepository(string $entityClass): EntityRepository
    {
        return self::getEntityManager()->getRepository($entityClass);
    }

    /**
     * @param array<string,mixed|array<string,mixed>> $criteria
     */
    private static function getResultCount(EntityRepository $entityRepository, array $criteria): int
    {
        $qb = $entityRepository->createQueryBuilder('a');
        $qb->select('count(a.id)');
        $parameterCount = 1;

        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                if ($value === []) {
                    // if $value is empty, make sure no relation exists
                    $joinAlias = sprintf('j%d', $parameterCount++);
                    $qb->leftJoin(sprintf('a.%s', $field), $joinAlias);
                    $qb->andWhere(sprintf('%s.id IS NULL', $joinAlias));
                } else {
                    $joinAlias = sprintf('j%d', $parameterCount);
                    $qb->join(sprintf('a.%s', $field), $joinAlias);

                    foreach ($value as $relatedField => $relatedValue) {
                        $paramName = sprintf('p%d', $parameterCount++);
                        $qb->andWhere(sprintf('%s.%s = :%s', $joinAlias, $relatedField, $paramName));
                        $qb->setParameter($paramName, $relatedValue);
                    }
                }
            } else {
                $paramName = sprintf('p%d', $parameterCount++);
                $qb->andWhere(sprintf('a.%s = :%s', $field, $paramName));
                $qb->setParameter($paramName, $value);
            }
        }

        $result = $qb->getQuery()->getSingleScalarResult();
        Assert::integer($result);

        return $result;
    }
}
