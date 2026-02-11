<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Ingest\Process\TikaOnly;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shared\Domain\Ingest\Content\ContentExtractCache;
use Shared\Domain\Ingest\Content\ContentExtractOptions;
use Shared\Domain\Ingest\Content\Extractor\ContentExtractorKey;
use Shared\Domain\Ingest\Process\TikaOnly\IngestTikaOnlyCommand;
use Shared\Domain\Ingest\Process\TikaOnly\IngestTikaOnlyHandler;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Search\Index\SubType\SubTypeIndexer;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

use function array_values;

final class IngestTikaOnlyHandlerTest extends UnitTestCase
{
    private EntityManagerInterface&MockInterface $doctrine;
    private ContentExtractCache&MockInterface $contentExtractCache;
    private SubTypeIndexer&MockInterface $subTypeIndexer;
    private LoggerInterface&MockInterface $logger;
    private EntityRepository&MockInterface $repository;
    private IngestTikaOnlyHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctrine = Mockery::mock(EntityManagerInterface::class);
        $this->contentExtractCache = Mockery::mock(ContentExtractCache::class);
        $this->subTypeIndexer = Mockery::mock(SubTypeIndexer::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->repository = Mockery::mock(EntityRepository::class);

        $this->handler = new IngestTikaOnlyHandler(
            $this->doctrine,
            $this->logger,
            $this->contentExtractCache,
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
        $entity = Mockery::mock(Document::class);
        $entity->shouldReceive('getId')->andReturn(Uuid::v6());

        $command = new IngestTikaOnlyCommand(
            Uuid::v6(),
            $entityClass = $entity::class,
            false,
        );

        $this->doctrine->shouldReceive('getRepository')->once()->with($entityClass)->andReturn($this->repository);

        $this->repository->shouldReceive('find')->once()->andReturn($entity);

        $text = "lorem ipsum tika\nlorem ipsum tesseract";

        $this->contentExtractCache
            ->expects('getCombinedExtracts')
            ->with($entity, Mockery::on(
                static function (ContentExtractOptions $options): bool {
                    self::assertEquals([ContentExtractorKey::TIKA], array_values($options->getEnabledExtractors()));

                    return true;
                }
            ))
            ->andReturn($text);

        $this->subTypeIndexer
            ->expects('index')
            ->with(
                $entity,
                [],
                [
                    [
                        'page_nr' => 0,
                        'content' => $text,
                    ],
                ],
            );

        $this->handler->__invoke($command);
    }

    public function testInvokeLogsErrorWhenElasticIndexFails(): void
    {
        $entity = Mockery::mock(Document::class);
        $entity
            ->shouldReceive('getId')
            ->andReturn($entityId = Mockery::mock(Uuid::class));

        $command = new IngestTikaOnlyCommand(
            Uuid::v6(),
            $entityClass = $entity::class,
            false,
        );

        $this->doctrine->shouldReceive('getRepository')->once()->with($entityClass)->andReturn($this->repository);

        $this->repository->shouldReceive('find')->once()->andReturn($entity);

        $text = "lorem ipsum tika\nlorem ipsum tesseract";

        $this->contentExtractCache
            ->expects('getCombinedExtracts')
            ->with($entity, Mockery::on(
                static function (ContentExtractOptions $options): bool {
                    self::assertEquals([ContentExtractorKey::TIKA], array_values($options->getEnabledExtractors()));

                    return true;
                }
            ))
            ->andReturn($text);

        $this->subTypeIndexer
            ->expects('index')
            ->with(
                $entity,
                [],
                [
                    [
                        'page_nr' => 0,
                        'content' => $text,
                    ],
                ],
            )
            ->andThrow($thrownException = new RuntimeException('oops'));

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
