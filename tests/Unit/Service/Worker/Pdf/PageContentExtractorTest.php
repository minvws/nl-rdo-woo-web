<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Worker\Pdf;

use App\Domain\Ingest\Content\ContentExtractCollection;
use App\Domain\Ingest\Content\ContentExtractOptions;
use App\Domain\Ingest\Content\ContentExtractService;
use App\Domain\Ingest\Content\Extractor\ContentExtractorKey;
use App\Domain\Publication\EntityWithFileInfo;
use App\Domain\Publication\FileInfo;
use App\Domain\Search\Index\SubType\SubTypeIndexer;
use App\Service\Stats\WorkerStatsService;
use App\Service\Worker\Pdf\Extractor\PageContentExtractor;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

final class PageContentExtractorTest extends UnitTestCase
{
    private LoggerInterface&MockInterface $logger;
    private ContentExtractService&MockInterface $contentExtractService;
    private SubTypeIndexer&MockInterface $subTypeIndexer;
    private WorkerStatsService&MockInterface $statsService;
    private EntityWithFileInfo&MockInterface $entity;
    private FileInfo&MockInterface $fileInfo;
    private PageContentExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->contentExtractService = \Mockery::mock(ContentExtractService::class);
        $this->subTypeIndexer = \Mockery::mock(SubTypeIndexer::class);
        $this->statsService = \Mockery::mock(WorkerStatsService::class);
        $this->entity = \Mockery::mock(EntityWithFileInfo::class);
        $this->fileInfo = \Mockery::mock(FileInfo::class);

        $this->extractor = new PageContentExtractor(
            $this->logger,
            $this->subTypeIndexer,
            $this->contentExtractService,
            $this->statsService,
        );
    }

    public function testExtract(): void
    {
        $pageNr = 123;

        $this->fileInfo
            ->shouldReceive('isPaginatable')
            ->once()
            ->andReturnTrue();

        $this->entity
            ->shouldReceive('getFileInfo')
            ->andReturn($this->fileInfo);

        $this->subTypeIndexer
            ->shouldReceive('updatePage')
            ->once()
            ->with($this->entity, $pageNr, "lorem ipsum tika\nlorem ipsum tesseract");

        $this->statsService
            ->shouldReceive('measure')
            ->once()
            ->andReturnUsing(function (string $key, \Closure $closure) {
                return $closure();
            });

        $this->statsService
            ->shouldReceive('measure')
            ->once()
            ->with('index.full.entity', \Mockery::on(function (\Closure $closure) {
                $closure();

                return true;
            }));

        $text = "lorem ipsum tika\nlorem ipsum tesseract";
        $collection = \Mockery::mock(ContentExtractCollection::class);
        $collection->shouldReceive('getCombinedContent')->andReturn($text);

        $this->contentExtractService
            ->expects('getExtracts')
            ->with($this->entity, \Mockery::on(
                static function (ContentExtractOptions $options) use ($pageNr): bool {
                    self::assertFalse($options->hasRefresh());
                    self::assertCount(count(ContentExtractorKey::cases()), $options->getEnabledExtractors());
                    self::assertEquals($pageNr, $options->getPageNumber());

                    return true;
                }
            ))
            ->andReturn($collection);

        $this->extractor->extract($this->entity, $pageNr, false);
    }

    public function testExtractWhenUpdatingSubTypePageIndexFails(): void
    {
        $pageNr = 123;

        $this->fileInfo
            ->shouldReceive('isPaginatable')
            ->once()
            ->andReturnTrue();

        $this->entity
            ->shouldReceive('getFileInfo')
            ->andReturn($this->fileInfo);
        $this->entity
            ->shouldReceive('getId')
            ->andReturn($entityId = \Mockery::mock(Uuid::class));

        $this->subTypeIndexer
            ->shouldReceive('updatePage')
            ->once()
            ->with($this->entity, $pageNr, "lorem ipsum tika\nlorem ipsum tesseract")
            ->andThrow($thrownException = new \RuntimeException('indexPage failed'));

        $this->statsService
            ->shouldReceive('measure')
            ->once()
            ->andReturnUsing(function (string $key, \Closure $closure) {
                return $closure();
            });

        $this->statsService
            ->shouldReceive('measure')
            ->once()
            ->with('index.full.entity', \Mockery::on(function (\Closure $closure) {
                $closure();

                return true;
            }));

        $text = "lorem ipsum tika\nlorem ipsum tesseract";
        $collection = \Mockery::mock(ContentExtractCollection::class);
        $collection->shouldReceive('getCombinedContent')->andReturn($text);

        $this->contentExtractService
            ->expects('getExtracts')
            ->with($this->entity, \Mockery::on(
                static function (ContentExtractOptions $options) use ($pageNr): bool {
                    self::assertFalse($options->hasRefresh());
                    self::assertCount(count(ContentExtractorKey::cases()), $options->getEnabledExtractors());
                    self::assertEquals($pageNr, $options->getPageNumber());

                    return true;
                }
            ))
            ->andReturn($collection);

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Failed to index page', [
                'id' => $entityId,
                'class' => $this->entity::class,
                'pageNr' => $pageNr,
                'exception' => $thrownException->getMessage(),
            ]);

        $this->extractor->extract($this->entity, $pageNr, false);
    }
}
