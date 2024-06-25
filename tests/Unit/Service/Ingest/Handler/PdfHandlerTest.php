<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Ingest\Handler;

use App\Domain\Ingest\IngestPdfMessage;
use App\Entity\Document;
use App\Entity\FileInfo;
use App\Service\Ingest\Handler;
use App\Service\Ingest\Handler\PdfHandler;
use App\Service\Ingest\Options;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

final class PdfHandlerTest extends UnitTestCase
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
        $handler = new PdfHandler($this->bus, $this->logger);

        $this->assertInstanceOf(PdfHandler::class, $handler);
        $this->assertInstanceOf(Handler::class, $handler);
    }

    public function testHandle(): void
    {
        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getId')->twice()->andReturn($id = \Mockery::mock(Uuid::class));
        $options = \Mockery::mock(Options::class);
        $options->shouldReceive('forceRefresh')->andReturn($forceRefresh = true);

        $this->logger->shouldReceive('info')->once()->with('Dispatching ingest for PDF document', [
            'id' => $id,
            'class' => $document::class,
        ]);

        $this->bus->shouldReceive('dispatch')->with(\Mockery::on(function (IngestPdfMessage $message) use ($id, $document, $forceRefresh) {
            return $message->getEntityId() === $id
                && $message->getEntityClass() === $document::class
                && $message->getForceRefresh() === $forceRefresh;
        }))->andReturn(new Envelope(new \stdClass()));

        $handler = new PdfHandler($this->bus, $this->logger);
        $handler->handle($document, $options);
    }

    public function testCanHandleReturnTrue(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('getMimetype')->once()->andReturn('application/pdf');

        $handler = new PdfHandler($this->bus, $this->logger);
        $this->assertTrue($handler->canHandle($fileInfo));
    }

    public function testCanHandleReturnFalse(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('getMimetype')->once()->andReturn('application/json');

        $handler = new PdfHandler($this->bus, $this->logger);
        $this->assertFalse($handler->canHandle($fileInfo));
    }
}
