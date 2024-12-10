<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Ingest\Process\SubType\Strategy;

use App\Domain\Ingest\IngestDispatcher;
use App\Domain\Ingest\Process\IngestProcessOptions;
use App\Domain\Ingest\Process\SubType\Strategy\TikaOnlySubTypeIngestStrategy;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Document;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

final class TikaOnlySubTypeIngestStrategyTest extends UnitTestCase
{
    private IngestDispatcher&MockInterface $ingestDispatcher;
    private LoggerInterface&MockInterface $logger;
    private TikaOnlySubTypeIngestStrategy $strategy;

    protected function setUp(): void
    {
        $this->ingestDispatcher = \Mockery::mock(IngestDispatcher::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);

        $this->strategy = new TikaOnlySubTypeIngestStrategy($this->ingestDispatcher, $this->logger);
    }

    public function testHandle(): void
    {
        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getId')->andReturn($id = \Mockery::mock(Uuid::class));

        $options = \Mockery::mock(IngestProcessOptions::class);
        $options->shouldReceive('forceRefresh')->andReturn($forceRefresh = true);

        $this->logger->shouldReceive('info')->once()->with('Dispatching tika-only ingest for entity', [
            'id' => $id,
            'class' => $document::class,
        ]);

        $this->ingestDispatcher->expects('dispatchIngestTikaOnlyCommand')->with($document, $forceRefresh);

        $this->strategy->handle($document, $options);
    }

    public function testCanHandleReturnsTrueWhenFileIsUploaded(): void
    {
        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getFileInfo->isUploaded')->andReturnTrue();

        $this->assertTrue($this->strategy->canHandle($document));
    }

    public function testCanHandleReturnsFalseWhenFileIsNotUploaded(): void
    {
        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getFileInfo->isUploaded')->andReturnFalse();

        $this->assertFalse($this->strategy->canHandle($document));
    }
}
