<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Result;

use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Result\Covenant\CovenantResultMapper;
use App\Domain\Search\Result\ResultEntryInterface;
use App\Domain\Search\Result\ResultFactory;
use App\Domain\Search\Result\WooDecision\DocumentResultMapper;
use App\Domain\Search\Result\WooDecision\WooDecisionResultMapper;
use Jaytaph\TypeArray\TypeArray;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class ResultFactoryTest extends MockeryTestCase
{
    private WooDecisionResultMapper&MockInterface $wooDecisionMapper;
    private DocumentResultMapper&MockInterface $documentMapper;
    private CovenantResultMapper&MockInterface $covenantMapper;
    private ResultFactory $factory;

    public function setUp(): void
    {
        $this->wooDecisionMapper = \Mockery::mock(WooDecisionResultMapper::class);
        $this->documentMapper = \Mockery::mock(DocumentResultMapper::class);
        $this->covenantMapper = \Mockery::mock(CovenantResultMapper::class);

        $this->factory = new ResultFactory(
            $this->wooDecisionMapper,
            $this->documentMapper,
            $this->covenantMapper,
        );
    }

    public function testMapForWooDecisionIsForwarded(): void
    {
        $hit = \Mockery::mock(TypeArray::class);
        $hit->shouldReceive('getString')->with('[fields][type][0]')->andReturn(ElasticDocumentType::WOO_DECISION->value);

        $result = \Mockery::mock(ResultEntryInterface::class);
        $this->wooDecisionMapper->expects('map')->with($hit)->andReturn($result);

        $this->assertSame($result, $this->factory->map($hit));
    }

    public function testMapForCovenantIsForwarded(): void
    {
        $hit = \Mockery::mock(TypeArray::class);
        $hit->shouldReceive('getString')->with('[fields][type][0]')->andReturn(ElasticDocumentType::COVENANT->value);

        $result = \Mockery::mock(ResultEntryInterface::class);
        $this->covenantMapper->expects('map')->with($hit)->andReturn($result);

        $this->assertSame($result, $this->factory->map($hit));
    }

    public function testMapForDocumentIsForwarded(): void
    {
        $hit = \Mockery::mock(TypeArray::class);
        $hit->shouldReceive('getString')->with('[fields][type][0]')->andReturn(ElasticDocumentType::WOO_DECISION_DOCUMENT->value);

        $result = \Mockery::mock(ResultEntryInterface::class);
        $this->documentMapper->expects('map')->with($hit)->andReturn($result);

        $this->assertSame($result, $this->factory->map($hit));
    }
}
