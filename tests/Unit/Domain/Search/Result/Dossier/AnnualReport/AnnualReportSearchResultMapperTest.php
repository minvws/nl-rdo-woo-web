<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Result\Dossier\AnnualReport;

use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportRepository;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Result\Dossier\AnnualReport\AnnualReportSearchResultMapper;
use App\Domain\Search\Result\Dossier\DossierSearchResultBaseMapper;
use App\Domain\Search\Result\ResultEntryInterface;
use Jaytaph\TypeArray\TypeArray;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class AnnualReportSearchResultMapperTest extends MockeryTestCase
{
    private DossierSearchResultBaseMapper&MockInterface $baseMapper;
    private AnnualReportRepository&MockInterface $repository;
    private AnnualReportSearchResultMapper $mapper;

    public function setUp(): void
    {
        $this->baseMapper = \Mockery::mock(DossierSearchResultBaseMapper::class);
        $this->repository = \Mockery::mock(AnnualReportRepository::class);

        $this->mapper = new AnnualReportSearchResultMapper(
            $this->baseMapper,
            $this->repository,
        );
    }

    public function testSupports(): void
    {
        self::assertTrue($this->mapper->supports(ElasticDocumentType::ANNUAL_REPORT));
        self::assertFalse($this->mapper->supports(ElasticDocumentType::COVENANT));
    }

    public function testMapForwardsToBaseMapper(): void
    {
        $hit = \Mockery::mock(TypeArray::class);

        $expectedResult = \Mockery::mock(ResultEntryInterface::class);

        $this->baseMapper
            ->expects('map')
            ->with($hit, $this->repository, ElasticDocumentType::ANNUAL_REPORT)
            ->andReturn($expectedResult);

        $this->assertEquals(
            $expectedResult,
            $this->mapper->map($hit),
        );
    }
}
