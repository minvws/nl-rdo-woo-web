<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Worker\Pdf;

use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shared\Domain\Ingest\Content\ContentExtractCache;
use Shared\Domain\Ingest\Content\ContentExtractOptions;
use Shared\Domain\Ingest\Content\Extractor\ContentExtractorKey;
use Shared\Domain\Ingest\Process\PdfPage\PdfPageProcessingContext;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Domain\Search\Index\SubType\SubTypeIndexer;
use Shared\Service\Worker\Pdf\Extractor\PageContentExtractor;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

use function count;

final class PageContentExtractorTest extends UnitTestCase
{
    private LoggerInterface&MockInterface $logger;
    private ContentExtractCache&MockInterface $contentExtractCache;
    private SubTypeIndexer&MockInterface $subTypeIndexer;
    private EntityWithFileInfo&MockInterface $entity;
    private PageContentExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->contentExtractCache = Mockery::mock(ContentExtractCache::class);
        $this->subTypeIndexer = Mockery::mock(SubTypeIndexer::class);
        $this->entity = Mockery::mock(EntityWithFileInfo::class);

        $this->extractor = new PageContentExtractor(
            $this->logger,
            $this->subTypeIndexer,
            $this->contentExtractCache,
        );
    }

    public function testExtract(): void
    {
        $pageNr = 123;
        $content = "lorem ipsum tika\nlorem ipsum tesseract";

        $this->contentExtractCache
            ->expects('getCombinedExtracts')
            ->with($this->entity, Mockery::on(
                static function (ContentExtractOptions $options) use ($pageNr): bool {
                    self::assertCount(count(ContentExtractorKey::cases()), $options->getEnabledExtractors());
                    self::assertEquals($pageNr, $options->getPageNumber());

                    return true;
                },
            ))
            ->andReturn($content);

        $this->subTypeIndexer
            ->expects('updatePage')
            ->with($this->entity, $pageNr, $content);

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
            ->with($this->entity, Mockery::on(
                static function (ContentExtractOptions $options) use ($pageNr): bool {
                    self::assertCount(count(ContentExtractorKey::cases()), $options->getEnabledExtractors());
                    self::assertEquals($pageNr, $options->getPageNumber());

                    return true;
                },
            ))
            ->andReturn($content);

        $this->subTypeIndexer
            ->expects('updatePage')
            ->with($this->entity, $pageNr, $content);

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
            ->with($this->entity, Mockery::on(
                static function (ContentExtractOptions $options) use ($pageNr): bool {
                    self::assertCount(count(ContentExtractorKey::cases()), $options->getEnabledExtractors());
                    self::assertEquals($pageNr, $options->getPageNumber());

                    return true;
                },
            ))
            ->andReturn($content);

        $this->entity
            ->expects('getId')
            ->andReturn($entityId = Mockery::mock(Uuid::class));

        $this->subTypeIndexer
            ->expects('updatePage')
            ->with($this->entity, $pageNr, $content)
            ->andThrow($thrownException = new RuntimeException('indexPage failed'));

        $this->logger
            ->expects('error')
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
