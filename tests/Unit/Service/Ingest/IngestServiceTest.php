<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Ingest;

use App\Entity\Document;
use App\Service\Ingest\Handler;
use App\Service\Ingest\IngestLogger;
use App\Service\Ingest\IngestService;
use App\Service\Ingest\Options;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class IngestServiceTest extends MockeryTestCase
{
    private Handler|MockInterface $handlerA;

    private Handler|Mockery\LegacyMockInterface|MockInterface $handlerB;

    private Handler|Mockery\LegacyMockInterface|MockInterface $handlerC;

    private IngestLogger|MockInterface $ingestLogger;

    private IngestService $ingestService;

    public function setUp(): void
    {
        $this->handlerA = \Mockery::mock(Handler::class);
        $this->handlerB = \Mockery::mock(Handler::class);
        $this->handlerC = \Mockery::mock(Handler::class);

        $this->ingestLogger = \Mockery::mock(IngestLogger::class);

        $this->ingestService = new IngestService(
            new \ArrayIterator([$this->handlerA, $this->handlerB, $this->handlerC]),
            $this->ingestLogger
        );

        parent::setUp();
    }

    public function testIngestUsesFirstMatchingHandler(): void
    {
        $pdfType = 'application/pdf';

        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getMimetype')->zeroOrMoreTimes()->andReturn($pdfType);
        $document->shouldReceive('getFilename')->zeroOrMoreTimes()->andReturn('test.pdf');

        $options = new Options();

        $this->handlerA->shouldReceive('canHandle')->with($pdfType)->andReturnFalse();
        $this->handlerB->shouldReceive('canHandle')->with($pdfType)->andReturnTrue();
        $this->handlerC->shouldNotReceive('canHandle');

        $this->ingestLogger->shouldReceive('success')->with($document, \Mockery::any(), \Mockery::any());
        $this->handlerB->shouldReceive('handle')->with($document, $options);

        $this->ingestService->ingest($document, $options);
    }

    public function testIngestTriggersIngestErrorWhenThereIsNoMatchingHandler(): void
    {
        $pdfType = 'application/pdf';

        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getMimetype')->zeroOrMoreTimes()->andReturn($pdfType);

        $options = new Options();

        $this->handlerA->shouldReceive('canHandle')->with($pdfType)->andReturnFalse();
        $this->handlerB->shouldReceive('canHandle')->with($pdfType)->andReturnFalse();
        $this->handlerC->shouldReceive('canHandle')->with($pdfType)->andReturnFalse();

        $this->ingestLogger->shouldReceive('error')->with($document, \Mockery::any(), \Mockery::any());

        $this->ingestService->ingest($document, $options);
    }
}
