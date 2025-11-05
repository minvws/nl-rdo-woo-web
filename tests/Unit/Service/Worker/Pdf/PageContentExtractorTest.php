<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Worker\Pdf;

use App\Domain\Ingest\Content\ContentExtractCache;
use App\Domain\Ingest\Content\ContentExtractCollection;
use App\Domain\Ingest\Content\ContentExtractOptions;
use App\Domain\Ingest\Content\Extractor\ContentExtractorKey;
use App\Domain\Ingest\Process\PdfPage\PdfPageProcessingContext;
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
    private ContentExtractCache&MockInterface $contentExtractCache;
    private SubTypeIndexer&MockInterface $subTypeIndexer;
    private WorkerStatsService&MockInterface $statsService;
    private EntityWithFileInfo&MockInterface $entity;
    private FileInfo&MockInterface $fileInfo;
    private PageContentExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->contentExtractCache = \Mockery::mock(ContentExtractCache::class);
        $this->subTypeIndexer = \Mockery::mock(SubTypeIndexer::class);
        $this->statsService = \Mockery::mock(WorkerStatsService::class);

        $this->fileInfo = \Mockery::mock(FileInfo::class);
        $this->fileInfo->shouldReceive('getHash')->andReturn('foobar');

        $this->entity = \Mockery::mock(EntityWithFileInfo::class);
        $this->entity->shouldReceive('getFileInfo')->andReturn($this->fileInfo);

        $this->extractor = new PageContentExtractor(
            $this->logger,
            $this->subTypeIndexer,
            $this->contentExtractCache,
            $this->statsService,
        );
    }

    public function testExtract(): void
    {
        $pageNr = 123;
        $content = "lorem ipsum tika\nlorem ipsum tesseract";

        $this->contentExtractCache
            ->expects('getCombinedExtracts')
            ->with($this->entity, \Mockery::on(
                static function (ContentExtractOptions $options) use ($pageNr): bool {
                    self::assertCount(count(ContentExtractorKey::cases()), $options->getEnabledExtractors());
                    self::assertEquals($pageNr, $options->getPageNumber());

                    return true;
                }
            ))
            ->andReturn($content);

        $this->subTypeIndexer
            ->shouldReceive('updatePage')
            ->once()
            ->with($this->entity, $pageNr, $content);

        $this->statsService
            ->shouldReceive('measure')
            ->once()
            ->with(
                'index.full.entity',
                \Mockery::on(static function (\Closure $closure) {
                    $closure();

                    return true;
                }),
            );

        $workDir = '/foo/bar';
        $localDocument = '/baz.pdf';
        $context = new PdfPageProcessingContext($this->entity, $pageNr, $workDir, $localDocument);
        $context->setLocalPageDocument('/baz_123.pdf');

        $this->extractor->extract($context);
    }

    public function testExtractWithCacheHit(): void
    {
        $pageNr = 123;
        $content = "lorem ipsum tika\nlorem ipsum tesseract";

        $this->contentExtractCache
            ->expects('getCombinedExtracts')
            ->with($this->entity, \Mockery::on(
                static function (ContentExtractOptions $options) use ($pageNr): bool {
                    self::assertCount(count(ContentExtractorKey::cases()), $options->getEnabledExtractors());
                    self::assertEquals($pageNr, $options->getPageNumber());

                    return true;
                }
            ))
            ->andReturn($content);

        $this->subTypeIndexer
            ->shouldReceive('updatePage')
            ->once()
            ->with($this->entity, $pageNr, $content);

        $this->statsService
            ->shouldReceive('measure')
            ->once()
            ->with(
                'index.full.entity',
                \Mockery::on(static function (\Closure $closure) {
                    $closure();

                    return true;
                }),
            );

        $workDir = '/foo/bar';
        $localDocument = '/baz.pdf';
        $context = new PdfPageProcessingContext($this->entity, $pageNr, $workDir, $localDocument);
        $context->setLocalPageDocument('/baz_123.pdf');

        $this->extractor->extract($context);
    }

    public function testExtractWhenUpdatingSubTypePageIndexFails(): void
    {
        $pageNr = 123;
        $content = "lorem ipsum tika\nlorem ipsum tesseract";

        $this->contentExtractCache
            ->expects('getCombinedExtracts')
            ->with($this->entity, \Mockery::on(
                static function (ContentExtractOptions $options) use ($pageNr): bool {
                    self::assertCount(count(ContentExtractorKey::cases()), $options->getEnabledExtractors());
                    self::assertEquals($pageNr, $options->getPageNumber());

                    return true;
                }
            ))
            ->andReturn($content);

        $this->entity
            ->shouldReceive('getFileInfo')
            ->andReturn($this->fileInfo);
        $this->entity
            ->shouldReceive('getId')
            ->andReturn($entityId = \Mockery::mock(Uuid::class));

        $this->subTypeIndexer
            ->shouldReceive('updatePage')
            ->once()
            ->with($this->entity, $pageNr, $content)
            ->andThrow($thrownException = new \RuntimeException('indexPage failed'));

        $this->statsService
            ->shouldReceive('measure')
            ->once()
            ->with(
                'index.full.entity',
                \Mockery::on(static function (\Closure $closure) {
                    $closure();

                    return true;
                }),
            );

        $collection = \Mockery::mock(ContentExtractCollection::class);
        $collection->shouldReceive('getCombinedContent')->andReturn($content);

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Failed to index page', [
                'id' => $entityId,
                'class' => $this->entity::class,
                'pageNr' => $pageNr,
                'exception' => $thrownException->getMessage(),
            ]);

        $workDir = '/foo/bar';
        $localDocument = '/baz.pdf';
        $context = new PdfPageProcessingContext($this->entity, $pageNr, $workDir, $localDocument);
        $context->setLocalPageDocument('/baz_123.pdf');

        $this->extractor->extract($context);
    }
}
