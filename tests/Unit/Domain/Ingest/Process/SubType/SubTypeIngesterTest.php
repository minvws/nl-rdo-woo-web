<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Ingest\Process\SubType;

use ArrayIterator;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Ingest\Process\IngestProcessException;
use Shared\Domain\Ingest\Process\IngestProcessOptions;
use Shared\Domain\Ingest\Process\SubType\SubTypeIngester;
use Shared\Domain\Ingest\Process\SubType\SubTypeIngestStrategyInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class SubTypeIngesterTest extends UnitTestCase
{
    private SubTypeIngestStrategyInterface&MockInterface $strategyA;

    private SubTypeIngestStrategyInterface&MockInterface $strategyB;

    private SubTypeIngestStrategyInterface&MockInterface $strategyC;

    private SubTypeIngester $ingester;

    protected function setUp(): void
    {
        $this->strategyA = Mockery::mock(SubTypeIngestStrategyInterface::class);
        $this->strategyB = Mockery::mock(SubTypeIngestStrategyInterface::class);
        $this->strategyC = Mockery::mock(SubTypeIngestStrategyInterface::class);

        $this->ingester = new SubTypeIngester(
            new ArrayIterator([$this->strategyA, $this->strategyB, $this->strategyC]),
        );

        parent::setUp();
    }

    public function testIngestUsesFirstMatchingStrategy(): void
    {
        $document = Mockery::mock(Document::class);

        $options = new IngestProcessOptions();

        $this->strategyA->shouldReceive('canHandle')->with($document)->andReturnFalse();
        $this->strategyB->shouldReceive('canHandle')->with($document)->andReturnTrue();
        $this->strategyC->shouldNotReceive('canHandle');

        $this->strategyB->shouldReceive('handle')->with($document, $options);

        $this->ingester->ingest($document, $options);
    }

    public function testIngestChecksAllStrategiesAndThrowsExceptionIfNoneCanHandleTheIngest(): void
    {
        $document = Mockery::mock(Document::class);
        $document->shouldReceive('getId')->andReturn(Uuid::v6());

        $options = new IngestProcessOptions();

        $this->strategyA->expects('canHandle')->with($document)->andReturnFalse();
        $this->strategyB->expects('canHandle')->with($document)->andReturnFalse();
        $this->strategyC->expects('canHandle')->with($document)->andReturnFalse();

        $this->expectException(IngestProcessException::class);
        $this->ingester->ingest($document, $options);
    }
}
