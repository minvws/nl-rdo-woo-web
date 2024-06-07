<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Result\WooDecision;

use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Result\DossierTypeSearchResultMapper;
use App\Domain\Search\Result\ResultEntryInterface;
use App\Domain\Search\Result\WooDecision\WooDecisionSearchResultMapper;
use App\Repository\DossierRepository;
use Jaytaph\TypeArray\TypeArray;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class WooDecisionSearchResultMapperTest extends MockeryTestCase
{
    private DossierTypeSearchResultMapper&MockInterface $baseMapper;
    private DossierRepository&MockInterface $repository;
    private WooDecisionSearchResultMapper $mapper;

    public function setUp(): void
    {
        $this->baseMapper = \Mockery::mock(DossierTypeSearchResultMapper::class);
        $this->repository = \Mockery::mock(DossierRepository::class);

        $this->mapper = new WooDecisionSearchResultMapper(
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
            ->with(
                $hit,
                $this->repository,
                ElasticDocumentType::WOO_DECISION,
                ['title', 'summary', 'decision_content'],
            )
            ->andReturn($expectedResult);

        $this->assertEquals(
            $expectedResult,
            $this->mapper->map($hit),
        );
    }
}
