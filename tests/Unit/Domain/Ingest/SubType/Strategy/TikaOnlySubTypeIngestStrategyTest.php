<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Ingest\SubType\Strategy;

use App\Domain\Ingest\IngestOptions;
use App\Domain\Ingest\SubType\Strategy\TikaOnlySubTypeIngestStrategy;
use App\Domain\Ingest\TikaOnly\IngestTikaOnlyCommand;
use App\Entity\Document;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

final class TikaOnlySubTypeIngestStrategyTest extends UnitTestCase
{
    private MessageBusInterface&MockInterface $bus;
    private LoggerInterface&MockInterface $logger;
    private TikaOnlySubTypeIngestStrategy $strategy;

    protected function setUp(): void
    {
        $this->bus = \Mockery::mock(MessageBusInterface::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);

        $this->strategy = new TikaOnlySubTypeIngestStrategy($this->bus, $this->logger);
    }

    public function testHandle(): void
    {
        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getId')->twice()->andReturn($id = \Mockery::mock(Uuid::class));

        $options = \Mockery::mock(IngestOptions::class);
        $options->shouldReceive('forceRefresh')->andReturn($forceRefresh = true);

        $this->logger->shouldReceive('info')->once()->with('Dispatching tika-only ingest for entity', [
            'id' => $id,
            'class' => $document::class,
        ]);

        $this->bus
            ->shouldReceive('dispatch')
            ->with(\Mockery::on(
                function (IngestTikaOnlyCommand $message) use ($id, $document, $forceRefresh) {
                    return $message->getEntityId() === $id
                        && $message->getEntityClass() === $document::class
                        && $message->getForceRefresh() === $forceRefresh;
                })
            )
            ->andReturn(new Envelope(new \stdClass()));

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
