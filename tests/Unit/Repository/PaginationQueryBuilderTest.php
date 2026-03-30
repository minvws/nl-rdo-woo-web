<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Department\Department;
use Shared\Repository\PaginationQueryBuilder;
use Shared\Tests\Unit\UnitTestCase;
use Webmozart\Assert\Assert;

use function base64_encode;
use function json_encode;

class PaginationQueryBuilderTest extends UnitTestCase
{
    private EntityManagerInterface&MockInterface $entityManager;
    private EntityRepository&MockInterface $entityRepository;
    private PaginationQueryBuilder $paginationQueryBuilder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->entityManager = Mockery::mock(EntityManagerInterface::class);
        $this->entityRepository = Mockery::mock(EntityRepository::class);
        $this->paginationQueryBuilder = new PaginationQueryBuilder($this->entityManager);
    }

    public function testGetPaginatedWithoutCursor(): void
    {
        $entityClass = Department::class;
        $itemsPerPage = 10;

        $queryBuilderMock = Mockery::mock(QueryBuilder::class);
        $queryBuilderMock->expects('orderBy')
            ->with('entity.id', 'ASC')
            ->andReturnSelf();
        $queryBuilderMock->expects('setMaxResults')
            ->with($itemsPerPage + 1)
            ->andReturnSelf();
        $this->entityRepository->expects('createQueryBuilder')
            ->with('entity')
            ->andReturn($queryBuilderMock);
        $this->entityManager->expects('getRepository')
            ->with($entityClass)
            ->andReturn($this->entityRepository);

        $result = $this->paginationQueryBuilder->getPaginated($entityClass, $itemsPerPage, null);

        $this->assertSame($queryBuilderMock, $result);
    }

    public function testGetPaginatedWithCursor(): void
    {
        $entityClass = Department::class;
        $cursorId = 123;

        $queryBuilderMock = Mockery::mock(QueryBuilder::class);
        $queryBuilderMock->expects('andWhere')
            ->with('entity.id > :id')
            ->andReturnSelf();
        $queryBuilderMock->expects('setParameter')
            ->with('id', $cursorId)
            ->andReturnSelf();
        $queryBuilderMock->expects('orderBy')
            ->andReturnSelf();
        $queryBuilderMock->expects('setMaxResults')
            ->andReturnSelf();

        $this->entityRepository->expects('createQueryBuilder')
            ->andReturn($queryBuilderMock);
        $this->entityManager->expects('getRepository')
            ->andReturn($this->entityRepository);

        $json = json_encode(['id' => $cursorId]);
        Assert::string($json);

        $this->paginationQueryBuilder->getPaginated($entityClass, 10, base64_encode($json));
    }
}
