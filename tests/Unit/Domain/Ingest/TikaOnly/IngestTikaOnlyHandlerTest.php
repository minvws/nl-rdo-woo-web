<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Ingest\TikaOnly;

use App\Domain\Ingest\TikaOnly\IngestTikaOnlyCommand;
use App\Domain\Ingest\TikaOnly\IngestTikaOnlyHandler;
use App\Domain\Search\Index\SubType\SubTypeIndexer;
use App\Entity\Document;
use App\Entity\FileInfo;
use App\Service\Storage\EntityStorageService;
use App\Service\Worker\Pdf\Tools\TikaService;
use App\Tests\Unit\UnitTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

final class IngestTikaOnlyHandlerTest extends UnitTestCase
{
    private EntityManagerInterface&MockInterface $doctrine;
    private EntityStorageService&MockInterface $entityStorage;
    private TikaService&MockInterface $tika;
    private SubTypeIndexer&MockInterface $subTypeIndexer;
    private LoggerInterface&MockInterface $logger;
    private EntityRepository&MockInterface $repository;
    private IngestTikaOnlyHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrine = \Mockery::mock(EntityManagerInterface::class);
        $this->entityStorage = \Mockery::mock(EntityStorageService::class);
        $this->tika = \Mockery::mock(TikaService::class);
        $this->subTypeIndexer = \Mockery::mock(SubTypeIndexer::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->repository = \Mockery::mock(EntityRepository::class);

        $this->handler = new IngestTikaOnlyHandler(
            $this->doctrine,
            $this->entityStorage,
            $this->logger,
            $this->tika,
            $this->subTypeIndexer,
        );
    }

    public function testInvokeLogsWarningAndReturnsEarlyWhenEntityIsNotFound(): void
    {
        $command = new IngestTikaOnlyCommand(
            Uuid::v6(),
            Document::class,
            false,
        );

        $this->doctrine->shouldReceive('getRepository')->once()->with(Document::class)->andReturn($this->repository);

        $this->repository->shouldReceive('find')->once()->andReturnNull();

        $this->logger->expects('warning');

        $this->handler->__invoke($command);
    }

    public function testInvokeLogsWarningAndReturnsEarlyWhenEntityFileCannotBeDownloaded(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('isUploaded')->once()->andReturnTrue();

        $entity = \Mockery::mock(Document::class);
        $entity->shouldReceive('getFileInfo')->andReturn($fileInfo);

        $command = new IngestTikaOnlyCommand(
            Uuid::v6(),
            $entityClass = $entity::class,
            false,
        );

        $this->doctrine->shouldReceive('getRepository')->once()->with($entityClass)->andReturn($this->repository);

        $this->repository->shouldReceive('find')->once()->andReturn($entity);

        $this->entityStorage->expects('downloadEntity')->with($entity)->andReturnFalse();

        $this->logger->expects('warning');

        $this->handler->__invoke($command);
    }

    public function testInvokeSuccessfully(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('isUploaded')->once()->andReturnTrue();
        $fileInfo->shouldReceive('getMimetype')->once()->andReturn('application/msword');

        $entity = \Mockery::mock(Document::class);
        $entity->shouldReceive('getFileInfo')->andReturn($fileInfo);
        $entity->shouldReceive('getId')->andReturn(Uuid::v6());

        $command = new IngestTikaOnlyCommand(
            Uuid::v6(),
            $entityClass = $entity::class,
            false,
        );

        $this->doctrine->shouldReceive('getRepository')->once()->with($entityClass)->andReturn($this->repository);

        $this->repository->shouldReceive('find')->once()->andReturn($entity);

        $this->entityStorage->expects('downloadEntity')->with($entity)->andReturn('foo/bar.txt');
        $this->tika
            ->expects('extract')
            ->with('foo/bar.txt', 'application/msword')
            ->andReturn(['X-TIKA:content' => 'foo bar']);

        $this->subTypeIndexer->expects('updatePage')->with($entity, 0, 'foo bar');

        $this->handler->__invoke($command);
    }

    public function testInvokeLogsErrorWhenElasticIndexFails(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('isUploaded')->once()->andReturnTrue();
        $fileInfo->shouldReceive('getMimetype')->once()->andReturn('application/msword');

        $entity = \Mockery::mock(Document::class);
        $entity->shouldReceive('getFileInfo')->andReturn($fileInfo);
        $entity->shouldReceive('getId')->andReturn(Uuid::v6());

        $command = new IngestTikaOnlyCommand(
            \Mockery::mock(Uuid::class),
            $entityClass = $entity::class,
            false,
        );

        $this->doctrine->shouldReceive('getRepository')->once()->with($entityClass)->andReturn($this->repository);

        $this->repository->shouldReceive('find')->once()->andReturn($entity);

        $this->entityStorage->expects('downloadEntity')->with($entity)->andReturn('foo/bar.txt');
        $this->tika
            ->expects('extract')
            ->with('foo/bar.txt', 'application/msword')
            ->andReturn(['X-TIKA:content' => 'foo bar']);

        $this->subTypeIndexer->expects('updatePage')->with($entity, 0, 'foo bar')->andThrow(new \RuntimeException());

        $this->logger->expects('error');

        $this->handler->__invoke($command);
    }
}
