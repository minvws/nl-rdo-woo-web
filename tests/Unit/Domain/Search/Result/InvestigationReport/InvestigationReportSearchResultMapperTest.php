<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Result\InvestigationReport;

use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportRepository;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Result\DossierTypeSearchResultMapper;
use App\Domain\Search\Result\InvestigationReport\InvestigationReportSearchResultMapper;
use App\Domain\Search\Result\ResultEntryInterface;
use Jaytaph\TypeArray\TypeArray;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class InvestigationReportSearchResultMapperTest extends MockeryTestCase
{
    private DossierTypeSearchResultMapper&MockInterface $baseMapper;
    private InvestigationReportRepository&MockInterface $repository;
    private InvestigationReportSearchResultMapper $mapper;

    public function setUp(): void
    {
        $this->baseMapper = \Mockery::mock(DossierTypeSearchResultMapper::class);
        $this->repository = \Mockery::mock(InvestigationReportRepository::class);

        $this->mapper = new InvestigationReportSearchResultMapper(
            $this->baseMapper,
            $this->repository,
        );
    }

    public function testMapForwardsToBaseMapper(): void
    {
        $hit = \Mockery::mock(TypeArray::class);

        $expectedResult = \Mockery::mock(ResultEntryInterface::class);

        $this->baseMapper
            ->expects('map')
            ->with($hit, $this->repository, ElasticDocumentType::INVESTIGATION_REPORT)
            ->andReturn($expectedResult);

        $this->assertEquals(
            $expectedResult,
            $this->mapper->map($hit),
        );
    }
}
