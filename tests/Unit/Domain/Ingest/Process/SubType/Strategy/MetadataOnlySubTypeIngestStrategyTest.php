<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Ingest\Process\SubType\Strategy;

use App\Domain\Ingest\IngestDispatcher;
use App\Domain\Ingest\Process\IngestProcessOptions;
use App\Domain\Ingest\Process\SubType\Strategy\MetadataOnlySubTypeIngestStrategy;
use App\Entity\Document;
use App\Entity\FileInfo;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

final class MetadataOnlySubTypeIngestStrategyTest extends UnitTestCase
{
    private IngestDispatcher&MockInterface $ingestDispatcher;
    private LoggerInterface&MockInterface $logger;
    private MetadataOnlySubTypeIngestStrategy $strategy;

    protected function setUp(): void
    {
        $this->ingestDispatcher = \Mockery::mock(IngestDispatcher::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->strategy = new MetadataOnlySubTypeIngestStrategy(
            $this->ingestDispatcher,
            $this->logger,
        );
    }

    public function testHandle(): void
    {
        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getId')->andReturn($id = \Mockery::mock(Uuid::class));
        $options = \Mockery::mock(IngestProcessOptions::class);

        $this->logger->shouldReceive('info')->once()->with('Dispatching ingest for metadata-only entity', [
            'id' => $id,
            'class' => $document::class,
        ]);

        $this->ingestDispatcher->shouldReceive('dispatchIngestMetadataOnlyCommandForEntity')->with(
            $document,
            true,
        );

        $this->strategy->handle($document, $options);
    }

    public function testCanHandleReturnsTrueWhenThereIsNoUploadedFile(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('isUploaded')->once()->andReturnFalse();

        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getFileInfo')->andReturn($fileInfo);

        $this->assertTrue($this->strategy->canHandle($document));
    }

    public function testCanHandleReturnsFalseWhenThereIsAnUploadedFile(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('isUploaded')->once()->andReturnTrue();

        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getFileInfo')->andReturn($fileInfo);

        $this->assertFalse($this->strategy->canHandle($document));
    }
}
