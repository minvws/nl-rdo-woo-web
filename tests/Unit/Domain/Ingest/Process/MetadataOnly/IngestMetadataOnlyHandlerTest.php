<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Ingest\Process\MetadataOnly;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shared\Domain\Ingest\Process\MetadataOnly\IngestMetadataOnlyCommand;
use Shared\Domain\Ingest\Process\MetadataOnly\IngestMetadataOnlyHandler;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Domain\Search\Index\SubType\SubTypeIndexer;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

final class IngestMetadataOnlyHandlerTest extends UnitTestCase
{
    private EntityManagerInterface&MockInterface $doctrine;
    private LoggerInterface&MockInterface $logger;
    private SubTypeIndexer&MockInterface $subTypeIndexer;
    private EntityRepository&MockInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrine = Mockery::mock(EntityManagerInterface::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->subTypeIndexer = Mockery::mock(SubTypeIndexer::class);
        $this->repository = Mockery::mock(EntityRepository::class);
    }

    public function testInvokeWithoutForce(): void
    {
        $message = new IngestMetadataOnlyCommand(
            Uuid::v6(),
            $entityClass = Mockery::mock(EntityWithFileInfo::class)::class,
            false,
        );

        $entity = Mockery::mock(EntityWithFileInfo::class);

        $this->doctrine->expects('getRepository')->with($entityClass)->andReturn($this->repository);
        $this->repository->expects('find')->andReturn($entity);
        $this->subTypeIndexer->expects('index')->with($entity, null, null);

        $handler = new IngestMetadataOnlyHandler($this->doctrine, $this->logger, $this->subTypeIndexer);
        $handler->__invoke($message);
    }

    public function testInvokeWithForce(): void
    {
        $message = new IngestMetadataOnlyCommand(
            Uuid::v6(),
            $entityClass = Mockery::mock(EntityWithFileInfo::class)::class,
            true,
        );

        $entity = Mockery::mock(EntityWithFileInfo::class);

        $this->doctrine->expects('getRepository')->with($entityClass)->andReturn($this->repository);
        $this->repository->expects('find')->andReturn($entity);
        $this->subTypeIndexer->expects('index')->with($entity, [], []);

        $handler = new IngestMetadataOnlyHandler($this->doctrine, $this->logger, $this->subTypeIndexer);
        $handler->__invoke($message);
    }

    public function testInvokeWhenEntityCannotBeFound(): void
    {
        $message = new IngestMetadataOnlyCommand(
            $id = Uuid::v6(),
            $entityClass = Mockery::mock(EntityWithFileInfo::class)::class,
            false,
        );

        $this->doctrine->expects('getRepository')->with($entityClass)->andReturn($this->repository);
        $this->repository->expects('find')->andReturnNull();
        $this->logger->expects('warning')->with('No entity found in IngestMetadataOnlyHandler', [
            'id' => $id->toRfc4122(),
            'class' => $entityClass,
        ]);
        $this->subTypeIndexer->shouldNotReceive('index');

        $handler = new IngestMetadataOnlyHandler($this->doctrine, $this->logger, $this->subTypeIndexer);
        $handler->__invoke($message);
    }

    public function testInvokeWhenUpdatingDocumentThrowsAnException(): void
    {
        $message = new IngestMetadataOnlyCommand(
            $id = Uuid::v6(),
            $entityClass = Mockery::mock(EntityWithFileInfo::class)::class,
            false,
        );

        $entity = Mockery::mock(EntityWithFileInfo::class);

        $this->doctrine->expects('getRepository')->with($entityClass)->andReturn($this->repository);
        $this->repository->expects('find')->andReturn($entity);
        $this->subTypeIndexer->expects('index')->andThrow($thrownException = new RuntimeException('My exception'));

        $this->logger->expects('error')->with('Failed to update ES document in IngestMetadataOnlyHandler', [
            'id' => $id->toRfc4122(),
            'class' => $entityClass,
            'exception' => $thrownException->getMessage(),
        ]);

        $handler = new IngestMetadataOnlyHandler($this->doctrine, $this->logger, $this->subTypeIndexer);
        $handler->__invoke($message);
    }
}
