<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Ingest\SubType;

use App\Domain\Ingest\IngestException;
use App\Domain\Ingest\IngestOptions;
use App\Domain\Ingest\SubType\SubTypeIngester;
use App\Domain\Ingest\SubType\SubTypeIngestStrategyInterface;
use App\Entity\Document;
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

        $options = new IngestOptions();

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

        $options = new IngestOptions();

        $this->strategyA->expects('canHandle')->with($document)->andReturnFalse();
        $this->strategyB->expects('canHandle')->with($document)->andReturnFalse();
        $this->strategyC->expects('canHandle')->with($document)->andReturnFalse();

        $this->expectException(IngestException::class);
        $this->ingester->ingest($document, $options);
    }
}
