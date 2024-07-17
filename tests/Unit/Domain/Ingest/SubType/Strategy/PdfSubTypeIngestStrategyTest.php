<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Ingest\SubType\Strategy;

use App\Domain\Ingest\IngestOptions;
use App\Domain\Ingest\Pdf\IngestPdfCommand;
use App\Domain\Ingest\SubType\Strategy\PdfSubTypeIngestStrategy;
use App\Domain\Ingest\SubType\SubTypeIngestStrategyInterface;
use App\Entity\Document;
use App\Entity\FileInfo;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

final class PdfSubTypeIngestStrategyTest extends UnitTestCase
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
        $strategy = new PdfSubTypeIngestStrategy($this->bus, $this->logger);

        $this->assertInstanceOf(PdfSubTypeIngestStrategy::class, $strategy);
        $this->assertInstanceOf(SubTypeIngestStrategyInterface::class, $strategy);
    }

    public function testHandle(): void
    {
        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getId')->twice()->andReturn($id = \Mockery::mock(Uuid::class));
        $options = \Mockery::mock(IngestOptions::class);
        $options->shouldReceive('forceRefresh')->andReturn($forceRefresh = true);

        $this->logger->shouldReceive('info')->once()->with('Dispatching ingest for PDF entity', [
            'id' => $id,
            'class' => $document::class,
        ]);

        $this->bus->shouldReceive('dispatch')->with(\Mockery::on(function (IngestPdfCommand $message) use ($id, $document, $forceRefresh) {
            return $message->getEntityId() === $id
                && $message->getEntityClass() === $document::class
                && $message->getForceRefresh() === $forceRefresh;
        }))->andReturn(new Envelope(new \stdClass()));

        $handler = new PdfSubTypeIngestStrategy($this->bus, $this->logger);
        $handler->handle($document, $options);
    }

    public function testCanHandleReturnsTrueWhenTheUploadIsAPdfAndPaginatable(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('getMimetype')->once()->andReturn('application/pdf');
        $fileInfo->shouldReceive('isPaginatable')->once()->andReturnTrue();

        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getFileInfo')->andReturn($fileInfo);

        $handler = new PdfSubTypeIngestStrategy($this->bus, $this->logger);
        $this->assertTrue($handler->canHandle($document));
    }

    public function testCanHandleReturnsFalseWhenTheUploadIsNotAPdf(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('getMimetype')->once()->andReturn('application/json');

        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getFileInfo')->andReturn($fileInfo);

        $handler = new PdfSubTypeIngestStrategy($this->bus, $this->logger);
        $this->assertFalse($handler->canHandle($document));
    }

    public function testCanHandleReturnsFalseWhenTheUploadIsAPdfAndNotPaginatable(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('getMimetype')->once()->andReturn('application/pdf');
        $fileInfo->shouldReceive('isPaginatable')->once()->andReturnFalse();

        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getFileInfo')->andReturn($fileInfo);

        $handler = new PdfSubTypeIngestStrategy($this->bus, $this->logger);
        $this->assertFalse($handler->canHandle($document));
    }
}
