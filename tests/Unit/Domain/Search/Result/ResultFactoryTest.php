<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Result;

use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Result\AnnualReport\AnnualReportSearchResultMapper;
use App\Domain\Search\Result\ComplaintJudgement\ComplaintJudgementSearchResultMapper;
use App\Domain\Search\Result\Covenant\CovenantSearchResultMapper;
use App\Domain\Search\Result\Disposition\DispositionSearchResultMapper;
use App\Domain\Search\Result\InvestigationReport\InvestigationReportSearchResultMapper;
use App\Domain\Search\Result\ResultEntryInterface;
use App\Domain\Search\Result\ResultFactory;
use App\Domain\Search\Result\WooDecision\DocumentSearchResultMapper;
use App\Domain\Search\Result\WooDecision\WooDecisionSearchResultMapper;
use Jaytaph\TypeArray\TypeArray;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class ResultFactoryTest extends MockeryTestCase
{
    private WooDecisionSearchResultMapper&MockInterface $wooDecisionMapper;
    private DocumentSearchResultMapper&MockInterface $documentMapper;
    private CovenantSearchResultMapper&MockInterface $covenantMapper;
    private AnnualReportSearchResultMapper&MockInterface $annualReportMapper;
    private InvestigationReportSearchResultMapper&MockInterface $investigationReportMapper;
    private DispositionSearchResultMapper&MockInterface $dispositionSearchResultMapper;
    private ComplaintJudgementSearchResultMapper&MockInterface $complaintJudgementResultMapper;
    private ResultFactory $factory;

    public function setUp(): void
    {
        $this->wooDecisionMapper = \Mockery::mock(WooDecisionSearchResultMapper::class);
        $this->documentMapper = \Mockery::mock(DocumentSearchResultMapper::class);
        $this->covenantMapper = \Mockery::mock(CovenantSearchResultMapper::class);
        $this->annualReportMapper = \Mockery::mock(AnnualReportSearchResultMapper::class);
        $this->investigationReportMapper = \Mockery::mock(InvestigationReportSearchResultMapper::class);
        $this->dispositionSearchResultMapper = \Mockery::mock(DispositionSearchResultMapper::class);
        $this->complaintJudgementResultMapper = \Mockery::mock(ComplaintJudgementSearchResultMapper::class);

        $this->factory = new ResultFactory(
            $this->wooDecisionMapper,
            $this->documentMapper,
            $this->covenantMapper,
            $this->annualReportMapper,
            $this->investigationReportMapper,
            $this->dispositionSearchResultMapper,
            $this->complaintJudgementResultMapper,
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

    public function testMapForAnnualReportIsForwarded(): void
    {
        $hit = \Mockery::mock(TypeArray::class);
        $hit->shouldReceive('getString')->with('[fields][type][0]')->andReturn(ElasticDocumentType::ANNUAL_REPORT->value);

        $result = \Mockery::mock(ResultEntryInterface::class);
        $this->annualReportMapper->expects('map')->with($hit)->andReturn($result);

        $this->assertSame($result, $this->factory->map($hit));
    }

    public function testMapForInvestigationReportIsForwarded(): void
    {
        $hit = \Mockery::mock(TypeArray::class);
        $hit->shouldReceive('getString')->with('[fields][type][0]')->andReturn(ElasticDocumentType::INVESTIGATION_REPORT->value);

        $result = \Mockery::mock(ResultEntryInterface::class);
        $this->investigationReportMapper->expects('map')->with($hit)->andReturn($result);

        $this->assertSame($result, $this->factory->map($hit));
    }

    public function testMapForDispositionIsForwarded(): void
    {
        $hit = \Mockery::mock(TypeArray::class);
        $hit->shouldReceive('getString')->with('[fields][type][0]')->andReturn(ElasticDocumentType::DISPOSITION->value);

        $result = \Mockery::mock(ResultEntryInterface::class);
        $this->dispositionSearchResultMapper->expects('map')->with($hit)->andReturn($result);

        $this->assertSame($result, $this->factory->map($hit));
    }

    public function testMapForComplaintJudgementIsForwarded(): void
    {
        $hit = \Mockery::mock(TypeArray::class);
        $hit->shouldReceive('getString')->with('[fields][type][0]')->andReturn(ElasticDocumentType::COMPLAINT_JUDGEMENT->value);

        $result = \Mockery::mock(ResultEntryInterface::class);
        $this->complaintJudgementResultMapper->expects('map')->with($hit)->andReturn($result);

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
