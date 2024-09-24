<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Ingest\Process\SubType\Strategy;

use App\Domain\Ingest\Process\IngestProcessOptions;
use App\Domain\Ingest\Process\MetadataOnly\IngestMetadataOnlyCommand;
use App\Domain\Ingest\Process\SubType\Strategy\MetadataOnlySubTypeIngestStrategy;
use App\Domain\Ingest\Process\SubType\SubTypeIngestStrategyInterface;
use App\Entity\Document;
use App\Entity\FileInfo;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

final class MetadataOnlySubTypeIngestStrategyTest extends UnitTestCase
{
    private MessageBusInterface&MockInterface $bus;
    private LoggerInterface&MockInterface $logger;

    protected function setUp(): void
    {
        $this->bus = \Mockery::mock(MessageBusInterface::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
    }

    public function testItCanBeInitialized(): void
    {
        $strategy = new MetadataOnlySubTypeIngestStrategy($this->bus, $this->logger);

        $this->assertInstanceOf(MetadataOnlySubTypeIngestStrategy::class, $strategy);
        $this->assertInstanceOf(SubTypeIngestStrategyInterface::class, $strategy);
    }

    public function testHandle(): void
    {
        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getId')->twice()->andReturn($id = \Mockery::mock(Uuid::class));
        $options = \Mockery::mock(IngestProcessOptions::class);

        $this->logger->shouldReceive('info')->once()->with('Dispatching ingest for metadata-only entity', [
            'id' => $id,
            'class' => $document::class,
        ]);

        $this->bus->shouldReceive('dispatch')->with(\Mockery::on(function (IngestMetadataOnlyCommand $message) use ($id, $document) {
            return $message->getEntityId() === $id
                && $message->getEntityClass() === $document::class
                && $message->getForceRefresh() === true;
        }))->andReturn(new Envelope(new \stdClass()));

        $handler = new MetadataOnlySubTypeIngestStrategy($this->bus, $this->logger);
        $handler->handle($document, $options);
    }

    public function testCanHandleReturnsTrueWhenThereIsNoUploadedFile(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('isUploaded')->once()->andReturnFalse();

        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getFileInfo')->andReturn($fileInfo);

        $handler = new MetadataOnlySubTypeIngestStrategy($this->bus, $this->logger);
        $this->assertTrue($handler->canHandle($document));
    }

    public function testCanHandleReturnsFalseWhenThereIsAnUploadedFile(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('isUploaded')->once()->andReturnTrue();

        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getFileInfo')->andReturn($fileInfo);

        $handler = new MetadataOnlySubTypeIngestStrategy($this->bus, $this->logger);
        $this->assertFalse($handler->canHandle($document));
    }
}
