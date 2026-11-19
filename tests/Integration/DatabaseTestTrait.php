<?php

declare(strict_types=1);

namespace Shared\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
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
        $queryBuilder = $entityRepository->createQueryBuilder('a');
        $queryBuilder->select('count(a.id)');
        $parameterCount = 1;

        foreach ($criteria as $field => $value) {
            $parameterCount = match (true) {
                $value === [] => self::addMissingRelationCriterion($queryBuilder, $field, $parameterCount),
                is_array($value) => self::addRelationCriteria($queryBuilder, $field, $value, $parameterCount),
                $value === null => self::addNullCriterion($queryBuilder, $field, $parameterCount),
                default => self::addCriterion($queryBuilder, $field, $value, $parameterCount),
            };
        }

        $result = $queryBuilder->getQuery()->getSingleScalarResult();
        Assert::integer($result);

        return $result;
    }

    private static function addMissingRelationCriterion(QueryBuilder $queryBuilder, string $field, int $parameterCount): int
    {
        $joinAlias = sprintf('j%d', $parameterCount++);
        $queryBuilder->leftJoin(sprintf('a.%s', $field), $joinAlias);
        $queryBuilder->andWhere(sprintf('%s.id IS NULL', $joinAlias));

        return $parameterCount;
    }

    /**
     * @param array<array-key,mixed> $relatedCriteria
     */
    private static function addRelationCriteria(
        QueryBuilder $queryBuilder,
        string $field,
        array $relatedCriteria,
        int $parameterCount,
    ): int {
        $joinAlias = sprintf('j%d', $parameterCount);
        $queryBuilder->join(sprintf('a.%s', $field), $joinAlias);

        foreach ($relatedCriteria as $relatedField => $relatedValue) {
            $paramName = sprintf('p%d', $parameterCount++);
            $queryBuilder->andWhere(sprintf('%s.%s = :%s', $joinAlias, $relatedField, $paramName));
            $queryBuilder->setParameter($paramName, $relatedValue);
        }

        return $parameterCount;
    }

    private static function addNullCriterion(QueryBuilder $queryBuilder, string $field, int $parameterCount): int
    {
        $queryBuilder->andWhere(sprintf('a.%s IS NULL', $field));

        return $parameterCount;
    }

    private static function addCriterion(QueryBuilder $queryBuilder, string $field, mixed $value, int $parameterCount): int
    {
        $paramName = sprintf('p%d', $parameterCount++);
        $queryBuilder->andWhere(sprintf('a.%s = :%s', $field, $paramName));
        $queryBuilder->setParameter($paramName, $value);

        return $parameterCount;
    }
}
