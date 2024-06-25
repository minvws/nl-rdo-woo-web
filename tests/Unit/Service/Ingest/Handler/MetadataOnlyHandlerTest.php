<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Ingest\Handler;

use App\Domain\Ingest\IngestMetadataOnlyMessage;
use App\Entity\Document;
use App\Entity\FileInfo;
use App\Service\Ingest\Handler;
use App\Service\Ingest\Handler\MetadataOnlyHandler;
use App\Service\Ingest\Options;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

final class MetadataOnlyHandlerTest extends UnitTestCase
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
        $handler = new MetadataOnlyHandler($this->bus, $this->logger);

        $this->assertInstanceOf(MetadataOnlyHandler::class, $handler);
        $this->assertInstanceOf(Handler::class, $handler);
    }

    public function testHandle(): void
    {
        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getId')->twice()->andReturn($id = \Mockery::mock(Uuid::class));
        $options = \Mockery::mock(Options::class);

        $this->logger->shouldReceive('info')->once()->with('Dispatching ingest for metadata-only document', [
            'id' => $id,
            'class' => $document::class,
        ]);

        $this->bus->shouldReceive('dispatch')->with(\Mockery::on(function (IngestMetadataOnlyMessage $message) use ($id, $document) {
            return $message->getEntityId() === $id
                && $message->getEntityClass() === $document::class
                && $message->getForceRefresh() === true;
        }))->andReturn(new Envelope(new \stdClass()));

        $handler = new MetadataOnlyHandler($this->bus, $this->logger);
        $handler->handle($document, $options);
    }

    public function testCanHandleReturnTrue(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('isUploaded')->once()->andReturnFalse();

        $handler = new MetadataOnlyHandler($this->bus, $this->logger);
        $this->assertTrue($handler->canHandle($fileInfo));
    }

    public function testCanHandleReturnFalse(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('isUploaded')->once()->andReturnTrue();

        $handler = new MetadataOnlyHandler($this->bus, $this->logger);
        $this->assertFalse($handler->canHandle($fileInfo));
    }
}
