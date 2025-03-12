<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Ingest\Process\SubType;

use App\Domain\Ingest\Process\IngestProcessException;
use App\Domain\Ingest\Process\IngestProcessOptions;
use App\Domain\Ingest\Process\SubType\SubTypeIngester;
use App\Domain\Ingest\Process\SubType\SubTypeIngestStrategyInterface;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

class SubTypeIngesterTest extends MockeryTestCase
{
    private SubTypeIngestStrategyInterface&MockInterface $strategyA;

    private SubTypeIngestStrategyInterface&MockInterface $strategyB;

    private SubTypeIngestStrategyInterface&MockInterface $strategyC;

    private SubTypeIngester $ingester;

    public function setUp(): void
    {
        $this->strategyA = \Mockery::mock(SubTypeIngestStrategyInterface::class);
        $this->strategyB = \Mockery::mock(SubTypeIngestStrategyInterface::class);
        $this->strategyC = \Mockery::mock(SubTypeIngestStrategyInterface::class);

        $this->ingester = new SubTypeIngester(
            new \ArrayIterator([$this->strategyA, $this->strategyB, $this->strategyC]),
        );

        parent::setUp();
    }

    public function testIngestUsesFirstMatchingStrategy(): void
    {
        $document = \Mockery::mock(Document::class);

        $options = new IngestProcessOptions();

        $this->strategyA->shouldReceive('canHandle')->with($document)->andReturnFalse();
        $this->strategyB->shouldReceive('canHandle')->with($document)->andReturnTrue();
        $this->strategyC->shouldNotReceive('canHandle');

        $this->strategyB->shouldReceive('handle')->with($document, $options);

        $this->ingester->ingest($document, $options);
    }

    public function testIngestChecksAllStrategiesAndThrowsExceptionIfNoneCanHandleTheIngest(): void
    {
        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getId')->andReturn(Uuid::v6());

        $options = new IngestProcessOptions();

        $this->strategyA->expects('canHandle')->with($document)->andReturnFalse();
        $this->strategyB->expects('canHandle')->with($document)->andReturnFalse();
        $this->strategyC->expects('canHandle')->with($document)->andReturnFalse();

        $this->expectException(IngestProcessException::class);
        $this->ingester->ingest($document, $options);
    }
}
