<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Ingest;

use App\Entity\Document;
use App\Entity\FileInfo;
use App\Service\Ingest\Handler;
use App\Service\Ingest\IngestService;
use App\Service\Ingest\Options;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

class IngestServiceTest extends MockeryTestCase
{
    private Handler&MockInterface $handlerA;

    private Handler&MockInterface $handlerB;

    private Handler&MockInterface $handlerC;

    private IngestService $ingestService;

    public function setUp(): void
    {
        $this->handlerA = \Mockery::mock(Handler::class);
        $this->handlerB = \Mockery::mock(Handler::class);
        $this->handlerC = \Mockery::mock(Handler::class);

        $this->ingestService = new IngestService(
            new \ArrayIterator([$this->handlerA, $this->handlerB, $this->handlerC]),
        );

        parent::setUp();
    }

    public function testIngestUsesFirstMatchingHandler(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('getName')->andReturn('test.pdf');

        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getFileInfo')->andReturn($fileInfo);

        $options = new Options();

        $this->handlerA->shouldReceive('canHandle')->with($fileInfo)->andReturnFalse();
        $this->handlerB->shouldReceive('canHandle')->with($fileInfo)->andReturnTrue();
        $this->handlerC->shouldNotReceive('canHandle');

        $this->handlerB->shouldReceive('handle')->with($document, $options);

        $this->ingestService->ingest($document, $options);
    }

    public function testIngestChecksAllHandlersAndThrowsExceptionIfNoneCanHandleTheIngest(): void
    {
        $fileInfo = \Mockery::mock(FileInfo::class);

        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getFileInfo')->andReturn($fileInfo);
        $document->shouldReceive('getId')->andReturn(Uuid::v6());

        $options = new Options();

        $this->handlerA->expects('canHandle')->with($fileInfo)->andReturnFalse();
        $this->handlerB->expects('canHandle')->with($fileInfo)->andReturnFalse();
        $this->handlerC->expects('canHandle')->with($fileInfo)->andReturnFalse();

        $this->expectException(\RuntimeException::class);
        $this->ingestService->ingest($document, $options);
    }
}
