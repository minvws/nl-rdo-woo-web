<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Ingest\Process\SubType\Strategy;

use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Shared\Domain\Ingest\IngestDispatcher;
use Shared\Domain\Ingest\Process\IngestProcessOptions;
use Shared\Domain\Ingest\Process\SubType\Strategy\MetadataOnlySubTypeIngestStrategy;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\FileInfo;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

final class MetadataOnlySubTypeIngestStrategyTest extends UnitTestCase
{
    private IngestDispatcher&MockInterface $ingestDispatcher;
    private LoggerInterface&MockInterface $logger;
    private MetadataOnlySubTypeIngestStrategy $strategy;

    protected function setUp(): void
    {
        $this->ingestDispatcher = Mockery::mock(IngestDispatcher::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->strategy = new MetadataOnlySubTypeIngestStrategy(
            $this->ingestDispatcher,
            $this->logger,
        );
    }

    public function testHandle(): void
    {
        $document = Mockery::mock(Document::class);
        $document->shouldReceive('getId')->andReturn($id = Mockery::mock(Uuid::class));
        $options = Mockery::mock(IngestProcessOptions::class);

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
        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('isUploaded')->once()->andReturnFalse();

        $document = Mockery::mock(Document::class);
        $document->shouldReceive('getFileInfo')->andReturn($fileInfo);

        $this->assertTrue($this->strategy->canHandle($document));
    }

    public function testCanHandleReturnsFalseWhenThereIsAnUploadedFile(): void
    {
        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('isUploaded')->once()->andReturnTrue();

        $document = Mockery::mock(Document::class);
        $document->shouldReceive('getFileInfo')->andReturn($fileInfo);

        $this->assertFalse($this->strategy->canHandle($document));
    }
}
