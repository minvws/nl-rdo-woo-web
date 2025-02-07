<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Ingest\Process\SubType\Strategy;

use App\Domain\Ingest\IngestDispatcher;
use App\Domain\Ingest\Process\IngestProcessOptions;
use App\Domain\Ingest\Process\SubType\Strategy\PdfSubTypeIngestStrategy;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Document;
use App\Domain\Publication\FileInfo;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

final class PdfSubTypeIngestStrategyTest extends UnitTestCase
{
    private IngestDispatcher&MockInterface $ingestDispatcher;
    private LoggerInterface&MockInterface $logger;
    private PdfSubTypeIngestStrategy $strategy;

    protected function setUp(): void
    {
        $this->ingestDispatcher = \Mockery::mock(IngestDispatcher::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);

        $this->strategy = new PdfSubTypeIngestStrategy($this->ingestDispatcher, $this->logger);
    }

    public function testHandle(): void
    {
        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getId')->andReturn($id = \Mockery::mock(Uuid::class));
        $options = \Mockery::mock(IngestProcessOptions::class);
        $options->shouldReceive('forceRefresh')->andReturn($forceRefresh = true);

        $this->logger->shouldReceive('info')->once()->with('Dispatching ingest for PDF entity', [
            'id' => $id,
            'class' => $document::class,
        ]);

        $this->ingestDispatcher->expects('dispatchIngestPdfCommand')->with(
            $document,
            $forceRefresh,
        );

        $this->strategy->handle($document, $options);
    }

    public function testCanHandleReturnsTrueWhenTheUploadIsAPdfAndPaginatable(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('getMimetype')->once()->andReturn('application/pdf');
        $fileInfo->shouldReceive('isPaginatable')->once()->andReturnTrue();

        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getFileInfo')->andReturn($fileInfo);

        $this->assertTrue($this->strategy->canHandle($document));
    }

    public function testCanHandleReturnsFalseWhenTheUploadIsNotAPdf(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('getMimetype')->once()->andReturn('application/json');

        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getFileInfo')->andReturn($fileInfo);

        $this->assertFalse($this->strategy->canHandle($document));
    }

    public function testCanHandleReturnsFalseWhenTheUploadIsAPdfAndNotPaginatable(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('getMimetype')->once()->andReturn('application/pdf');
        $fileInfo->shouldReceive('isPaginatable')->once()->andReturnFalse();

        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getFileInfo')->andReturn($fileInfo);

        $this->assertFalse($this->strategy->canHandle($document));
    }
}
