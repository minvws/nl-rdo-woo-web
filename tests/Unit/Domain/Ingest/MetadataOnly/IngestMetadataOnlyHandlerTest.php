<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Ingest\MetadataOnly;

use App\Domain\Ingest\MetadataOnly\IngestMetadataOnlyCommand;
use App\Domain\Ingest\MetadataOnly\IngestMetadataOnlyHandler;
use App\Domain\Search\Index\SubType\SubTypeIndexer;
use App\Entity\Document;
use App\Entity\EntityWithFileInfo;
use App\Tests\Unit\UnitTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
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

        $this->doctrine = \Mockery::mock(EntityManagerInterface::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->subTypeIndexer = \Mockery::mock(SubTypeIndexer::class);
        $this->repository = \Mockery::mock(EntityRepository::class);
    }

    public function testInvokeWithoutForce(): void
    {
        $message = new IngestMetadataOnlyCommand(
            \Mockery::mock(Uuid::class),
            $entityClass = \Mockery::mock(EntityWithFileInfo::class)::class,
            false,
        );

        // @TODO should be replaced by an instance of EntityWithFileInfo
        $entity = \Mockery::mock(Document::class);

        $this->doctrine->shouldReceive('getRepository')->once()->with($entityClass)->andReturn($this->repository);
        $this->repository->shouldReceive('find')->once()->andReturn($entity);
        $this->subTypeIndexer->shouldReceive('index')->once()->with($entity, null, null);

        $handler = new IngestMetadataOnlyHandler($this->doctrine, $this->logger, $this->subTypeIndexer);
        $handler->__invoke($message);
    }

    public function testInvokeWithForce(): void
    {
        $message = new IngestMetadataOnlyCommand(
            \Mockery::mock(Uuid::class),
            $entityClass = \Mockery::mock(EntityWithFileInfo::class)::class,
            true,
        );

        // @TODO should be replaced by an instance of EntityWithFileInfo
        $entity = \Mockery::mock(Document::class);

        $this->doctrine->shouldReceive('getRepository')->once()->with($entityClass)->andReturn($this->repository);
        $this->repository->shouldReceive('find')->once()->andReturn($entity);
        $this->subTypeIndexer->shouldReceive('index')->once()->with($entity, [], []);

        $handler = new IngestMetadataOnlyHandler($this->doctrine, $this->logger, $this->subTypeIndexer);
        $handler->__invoke($message);
    }

    public function testInvokeWhenEntityCannotBeFound(): void
    {
        $message = new IngestMetadataOnlyCommand(
            $id = \Mockery::mock(Uuid::class),
            $entityClass = \Mockery::mock(EntityWithFileInfo::class)::class,
            false,
        );

        $this->doctrine->shouldReceive('getRepository')->once()->with($entityClass)->andReturn($this->repository);
        $this->repository->shouldReceive('find')->once()->andReturnNull();
        $this->logger->shouldReceive('warning')->once()->with('No entity found in IngestMetadataOnlyHandler', [
            'id' => $id,
            'class' => $entityClass,
        ]);
        $this->subTypeIndexer->shouldNotReceive('index');

        $handler = new IngestMetadataOnlyHandler($this->doctrine, $this->logger, $this->subTypeIndexer);
        $handler->__invoke($message);
    }

    public function testInvokeWhenUpdatingDocumentThrowsAnException(): void
    {
        $message = new IngestMetadataOnlyCommand(
            $id = \Mockery::mock(Uuid::class),
            $entityClass = \Mockery::mock(EntityWithFileInfo::class)::class,
            false,
        );

        // @TODO should be replaced by an instance of EntityWithFileInfo
        $entity = \Mockery::mock(Document::class);

        $this->doctrine->shouldReceive('getRepository')->once()->with($entityClass)->andReturn($this->repository);
        $this->repository->shouldReceive('find')->once()->andReturn($entity);
        $this->subTypeIndexer->shouldReceive('index')->andThrow($thrownException = new \RuntimeException('My exception'));

        $this->logger->shouldReceive('error')->once()->with('Failed to update ES document in IngestMetadataOnlyHandler', [
            'id' => $id,
            'class' => $entityClass,
            'exception' => $thrownException->getMessage(),
        ]);

        $handler = new IngestMetadataOnlyHandler($this->doctrine, $this->logger, $this->subTypeIndexer);
        $handler->__invoke($message);
    }
}
