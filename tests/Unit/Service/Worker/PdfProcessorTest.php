<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Worker;

use App\Entity\EntityWithFileInfo;
use App\Service\Worker\Pdf\Extractor\EntityMetaDataExtractor;
use App\Service\Worker\Pdf\Extractor\PageContentExtractor;
use App\Service\Worker\Pdf\Extractor\PageExtractor;
use App\Service\Worker\Pdf\Extractor\ThumbnailExtractor;
use App\Service\Worker\PdfProcessor;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;

final class PdfProcessorTest extends UnitTestCase
{
    protected ThumbnailExtractor&MockInterface $thumbnailExtractor;
    protected EntityMetaDataExtractor&MockInterface $docContentExtractor;
    protected PageContentExtractor&MockInterface $pageContentExtractor;
    protected PageExtractor&MockInterface $pageExtractor;
    protected EntityWithFileInfo&MockInterface $entity;

    protected function setUp(): void
    {
        parent::setUp();

        $this->thumbnailExtractor = \Mockery::mock(ThumbnailExtractor::class);
        $this->docContentExtractor = \Mockery::mock(EntityMetaDataExtractor::class);
        $this->pageContentExtractor = \Mockery::mock(PageContentExtractor::class);
        $this->pageExtractor = \Mockery::mock(PageExtractor::class);
        $this->entity = \Mockery::mock(EntityWithFileInfo::class);
    }

    public function testProcessEntityPage(): void
    {
        $pageNr = 42;
        $forceRefresh = true;

        $this->pageExtractor
            ->shouldReceive('extract')
            ->once()
            ->with($this->entity, $pageNr, $forceRefresh);

        $this->thumbnailExtractor
            ->shouldReceive('extract')
            ->once()
            ->with($this->entity, $pageNr, $forceRefresh);

        $this->pageContentExtractor
            ->shouldReceive('extract')
            ->once()
            ->with($this->entity, $pageNr, $forceRefresh);

        $this->getInstance()->processEntityPage($this->entity, $pageNr, $forceRefresh);
    }

    public function testProcessEntity(): void
    {
        $forceRefresh = true;

        $this->docContentExtractor
            ->shouldReceive('extract')
            ->once()
            ->with($this->entity, $forceRefresh);

        $this->getInstance()->processEntity($this->entity, $forceRefresh);
    }

    private function getInstance(): PdfProcessor
    {
        return new PdfProcessor(
            $this->thumbnailExtractor,
            $this->docContentExtractor,
            $this->pageContentExtractor,
            $this->pageExtractor,
        );
    }
}
