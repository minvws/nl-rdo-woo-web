<?php

declare(strict_types=1);

namespace App\Tests\Unit\Repository;

use App\Domain\Department\Department;
use App\Repository\PaginationQueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Webmozart\Assert\Assert;

class PaginationQueryBuilderTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private EntityRepository&MockObject $entityRepository;
    private PaginationQueryBuilder $paginationQueryBuilder;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityRepository = $this->createMock(EntityRepository::class);
        $this->paginationQueryBuilder = new PaginationQueryBuilder($this->entityManager);
    }

    public function testGetPaginatedWithoutCursor(): void
    {
        $entityClass = Department::class;
        $itemsPerPage = 10;

        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $queryBuilderMock->expects($this->once())
            ->method('orderBy')
            ->with('entity.id', 'ASC')
            ->willReturnSelf();
        $queryBuilderMock->expects($this->once())
            ->method('setMaxResults')
            ->with($itemsPerPage + 1)
            ->willReturnSelf();
        $this->entityRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('entity')
            ->willReturn($queryBuilderMock);
        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with($entityClass)
            ->willReturn($this->entityRepository);

        $result = $this->paginationQueryBuilder->getPaginated($entityClass, $itemsPerPage, null);

        $this->assertSame($queryBuilderMock, $result);
    }

    public function testGetPaginatedWithCursor(): void
    {
        $entityClass = Department::class;
        $cursorId = 123;

        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $queryBuilderMock->expects($this->once())
            ->method('andWhere')
            ->with('entity.id > :id')
            ->willReturnSelf();
        $queryBuilderMock->expects($this->once())
            ->method('setParameter')
            ->with('id', $cursorId)
            ->willReturnSelf();
        $queryBuilderMock->expects($this->once())
            ->method('orderBy')
            ->willReturnSelf();
        $queryBuilderMock->expects($this->once())
            ->method('setMaxResults')
            ->willReturnSelf();

        $this->entityRepository->method('createQueryBuilder')->willReturn($queryBuilderMock);
        $this->entityManager->method('getRepository')->willReturn($this->entityRepository);

        $json = json_encode(['id' => $cursorId]);
        Assert::string($json);

        $this->paginationQueryBuilder->getPaginated($entityClass, 10, base64_encode($json));
    }
}
