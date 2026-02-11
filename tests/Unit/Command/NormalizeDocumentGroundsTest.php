<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Mockery;
use Shared\Command\NormalizeDocumentGrounds;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class NormalizeDocumentGroundsTest extends UnitTestCase
{
    public function testExecute(): void
    {
        $query = Mockery::mock(Query::class);
        $query->expects('toIterable')
            ->andReturn([]);

        $queryBuilder = Mockery::mock(QueryBuilder::class);
        $queryBuilder->expects('select')
            ->with('d')
            ->andReturn($queryBuilder);
        $queryBuilder->expects('getQuery')
            ->andReturn($query);

        $entityRepository = Mockery::mock(EntityRepository::class);
        $entityRepository->expects('createQueryBuilder')
            ->with('d')
            ->andReturn($queryBuilder);

        $entityManager = Mockery::mock(EntityManagerInterface::class);
        $entityManager->expects('getRepository')
            ->andReturn($entityRepository);
        $entityManager->expects('flush');

        $command = new NormalizeDocumentGrounds($entityManager);

        $application = new Application();
        $application->add($command);

        $command = $application->find(NormalizeDocumentGrounds::COMMAND_NAME);
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'mapping' => $this->getFaker()->word(),
        ]);

        self::assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testExecuteWithDryRun(): void
    {
        $query = Mockery::mock(Query::class);
        $query->expects('toIterable')
            ->andReturn([]);

        $queryBuilder = Mockery::mock(QueryBuilder::class);
        $queryBuilder->expects('select')
            ->with('d')
            ->andReturn($queryBuilder);
        $queryBuilder->expects('getQuery')
            ->andReturn($query);

        $entityRepository = Mockery::mock(EntityRepository::class);
        $entityRepository->expects('createQueryBuilder')
            ->with('d')
            ->andReturn($queryBuilder);

        $entityManager = Mockery::mock(EntityManagerInterface::class);
        $entityManager->expects('getRepository')
            ->andReturn($entityRepository);
        $entityManager->expects('flush')
            ->never();

        $command = new NormalizeDocumentGrounds($entityManager);

        $application = new Application();
        $application->add($command);

        $command = $application->find(NormalizeDocumentGrounds::COMMAND_NAME);
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'mapping' => $this->getFaker()->word(),
            '--dry-run' => true,
        ]);

        self::assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }
}
