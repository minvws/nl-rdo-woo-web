<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Worker;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Service\Worker\Pdf\Extractor\EntityMetaDataExtractor;
use Shared\Service\Worker\PdfProcessor;
use Shared\Tests\Unit\UnitTestCase;

final class PdfProcessorTest extends UnitTestCase
{
    private EntityMetaDataExtractor&MockInterface $docContentExtractor;
    private PdfProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->docContentExtractor = Mockery::mock(EntityMetaDataExtractor::class);
        $this->processor = new PdfProcessor(
            $this->docContentExtractor,
        );
    }

    public function testProcessEntity(): void
    {
        $entity = Mockery::mock(EntityWithFileInfo::class);

        $this->docContentExtractor
            ->expects('extract')
            ->with($entity);

        $this->processor->processEntity($entity);
    }
}
