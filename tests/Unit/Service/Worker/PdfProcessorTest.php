<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Worker;

use App\Domain\Publication\EntityWithFileInfo;
use App\Service\Worker\Pdf\Extractor\EntityMetaDataExtractor;
use App\Service\Worker\PdfProcessor;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;

final class PdfProcessorTest extends UnitTestCase
{
    private EntityMetaDataExtractor&MockInterface $docContentExtractor;
    private PdfProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->docContentExtractor = \Mockery::mock(EntityMetaDataExtractor::class);
        $this->processor = new PdfProcessor(
            $this->docContentExtractor,
        );
    }

    public function testProcessEntity(): void
    {
        $forceRefresh = true;
        $entity = \Mockery::mock(EntityWithFileInfo::class);

        $this->docContentExtractor
            ->expects('extract')
            ->with($entity, $forceRefresh);

        $this->processor->processEntity($entity, $forceRefresh);
    }
}
