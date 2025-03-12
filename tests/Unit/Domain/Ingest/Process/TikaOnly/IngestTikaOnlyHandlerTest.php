<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Ingest\Process\TikaOnly;

use App\Domain\Ingest\Content\ContentExtractCollection;
use App\Domain\Ingest\Content\ContentExtractOptions;
use App\Domain\Ingest\Content\ContentExtractService;
use App\Domain\Ingest\Content\Extractor\ContentExtractorKey;
use App\Domain\Ingest\Process\TikaOnly\IngestTikaOnlyCommand;
use App\Domain\Ingest\Process\TikaOnly\IngestTikaOnlyHandler;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Search\Index\SubType\SubTypeIndexer;
use App\Tests\Unit\UnitTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

final class IngestTikaOnlyHandlerTest extends UnitTestCase
{
    private EntityManagerInterface&MockInterface $doctrine;
    private ContentExtractService&MockInterface $contentExtractService;
    private SubTypeIndexer&MockInterface $subTypeIndexer;
    private LoggerInterface&MockInterface $logger;
    private EntityRepository&MockInterface $repository;
    private IngestTikaOnlyHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrine = \Mockery::mock(EntityManagerInterface::class);
        $this->contentExtractService = \Mockery::mock(ContentExtractService::class);
        $this->subTypeIndexer = \Mockery::mock(SubTypeIndexer::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->repository = \Mockery::mock(EntityRepository::class);

        $this->handler = new IngestTikaOnlyHandler(
            $this->doctrine,
            $this->logger,
            $this->contentExtractService,
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

    public function testInvokeSuccessfully(): void
    {
        $entity = \Mockery::mock(Document::class);
        $entity->shouldReceive('getId')->andReturn(Uuid::v6());

        $command = new IngestTikaOnlyCommand(
            Uuid::v6(),
            $entityClass = $entity::class,
            false,
        );

        $this->doctrine->shouldReceive('getRepository')->once()->with($entityClass)->andReturn($this->repository);

        $this->repository->shouldReceive('find')->once()->andReturn($entity);

        $text = "lorem ipsum tika\nlorem ipsum tesseract";
        $collection = \Mockery::mock(ContentExtractCollection::class);
        $collection->shouldReceive('getCombinedContent')->andReturn($text);

        $this->contentExtractService
            ->expects('getExtracts')
            ->with($entity, \Mockery::on(
                static function (ContentExtractOptions $options): bool {
                    self::assertFalse($options->hasRefresh());
                    self::assertEquals([ContentExtractorKey::TIKA], array_values($options->getEnabledExtractors()));

                    return true;
                }
            ))
            ->andReturn($collection);

        $this->subTypeIndexer->expects('updatePage')->with($entity, 0, $text);

        $this->handler->__invoke($command);
    }

    public function testInvokeLogsErrorWhenElasticIndexFails(): void
    {
        $entity = \Mockery::mock(Document::class);
        $entity
            ->shouldReceive('getId')
            ->andReturn($entityId = \Mockery::mock(Uuid::class));

        $command = new IngestTikaOnlyCommand(
            Uuid::v6(),
            $entityClass = $entity::class,
            false,
        );

        $this->doctrine->shouldReceive('getRepository')->once()->with($entityClass)->andReturn($this->repository);

        $this->repository->shouldReceive('find')->once()->andReturn($entity);

        $text = "lorem ipsum tika\nlorem ipsum tesseract";
        $collection = \Mockery::mock(ContentExtractCollection::class);
        $collection->shouldReceive('getCombinedContent')->andReturn($text);

        $this->contentExtractService
            ->expects('getExtracts')
            ->with($entity, \Mockery::on(
                static function (ContentExtractOptions $options): bool {
                    self::assertFalse($options->hasRefresh());
                    self::assertEquals([ContentExtractorKey::TIKA], array_values($options->getEnabledExtractors()));

                    return true;
                }
            ))
            ->andReturn($collection);

        $this->subTypeIndexer
            ->expects('updatePage')
            ->with($entity, 0, $text)
            ->andThrow($thrownException = new \RuntimeException('oops'));

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Failed to index tika content as page', [
                'id' => $entityId,
                'class' => $entity::class,
                'exception' => $thrownException->getMessage(),
            ]);

        $this->handler->__invoke($command);
    }
}
