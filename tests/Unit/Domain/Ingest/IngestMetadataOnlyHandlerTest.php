<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Ingest;

use App\Domain\Ingest\IngestMetadataOnlyHandler;
use App\Domain\Ingest\IngestMetadataOnlyMessage;
use App\Entity\Document;
use App\Entity\EntityWithFileInfo;
use App\Service\Elastic\ElasticService;
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
    private ElasticService&MockInterface $elasticService;
    private EntityRepository&MockInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrine = \Mockery::mock(EntityManagerInterface::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->elasticService = \Mockery::mock(ElasticService::class);
        $this->repository = \Mockery::mock(EntityRepository::class);
    }

    public function testInvokeWithoutForce(): void
    {
        $message = new IngestMetadataOnlyMessage(
            \Mockery::mock(Uuid::class),
            $entityClass = \Mockery::mock(EntityWithFileInfo::class)::class,
            false,
        );

        // @TODO should be replaced by an instance of EntityWithFileInfo
        $entity = \Mockery::mock(Document::class);

        $this->doctrine->shouldReceive('getRepository')->once()->with($entityClass)->andReturn($this->repository);
        $this->repository->shouldReceive('find')->once()->andReturn($entity);
        $this->elasticService->shouldReceive('updateDocument')->once()->with($entity, null, null);

        $handler = new IngestMetadataOnlyHandler($this->doctrine, $this->logger, $this->elasticService);
        $handler->__invoke($message);
    }

    public function testInvokeWithForce(): void
    {
        $message = new IngestMetadataOnlyMessage(
            \Mockery::mock(Uuid::class),
            $entityClass = \Mockery::mock(EntityWithFileInfo::class)::class,
            true,
        );

        // @TODO should be replaced by an instance of EntityWithFileInfo
        $entity = \Mockery::mock(Document::class);

        $this->doctrine->shouldReceive('getRepository')->once()->with($entityClass)->andReturn($this->repository);
        $this->repository->shouldReceive('find')->once()->andReturn($entity);
        $this->elasticService->shouldReceive('updateDocument')->once()->with($entity, [], []);

        $handler = new IngestMetadataOnlyHandler($this->doctrine, $this->logger, $this->elasticService);
        $handler->__invoke($message);
    }

    public function testInvokeWhenEntityCannotBeFound(): void
    {
        $message = new IngestMetadataOnlyMessage(
            $id = \Mockery::mock(Uuid::class),
            $entityClass = \Mockery::mock(EntityWithFileInfo::class)::class,
        );

        $this->doctrine->shouldReceive('getRepository')->once()->with($entityClass)->andReturn($this->repository);
        $this->repository->shouldReceive('find')->once()->andReturnNull();
        $this->logger->shouldReceive('warning')->once()->with('No document found for this message', [
            'id' => $id,
            'class' => $entityClass,
        ]);
        $this->elasticService->shouldNotReceive('updateDocument');

        $handler = new IngestMetadataOnlyHandler($this->doctrine, $this->logger, $this->elasticService);
        $handler->__invoke($message);
    }

    public function testInvokeWhenUpdatingDocumentThrowsAnException(): void
    {
        $message = new IngestMetadataOnlyMessage(
            $id = \Mockery::mock(Uuid::class),
            $entityClass = \Mockery::mock(EntityWithFileInfo::class)::class,
            false,
        );

        // @TODO should be replaced by an instance of EntityWithFileInfo
        $entity = \Mockery::mock(Document::class);

        $this->doctrine->shouldReceive('getRepository')->once()->with($entityClass)->andReturn($this->repository);
        $this->repository->shouldReceive('find')->once()->andReturn($entity);
        $this->elasticService->shouldReceive('updateDocument')->andThrow($thrownException = new \RuntimeException('My exception'));

        $this->logger->shouldReceive('error')->once()->with('Failed to ingest metadata-only document into ES', [
            'id' => $id,
            'class' => $entityClass,
            'exception' => $thrownException->getMessage(),
        ]);

        $handler = new IngestMetadataOnlyHandler($this->doctrine, $this->logger, $this->elasticService);
        $handler->__invoke($message);
    }
}
