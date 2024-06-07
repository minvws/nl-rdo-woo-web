<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Result\Disposition;

use App\Domain\Publication\Dossier\Type\Disposition\DispositionRepository;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Result\Disposition\DispositionSearchResultMapper;
use App\Domain\Search\Result\DossierTypeSearchResultMapper;
use App\Domain\Search\Result\ResultEntryInterface;
use Jaytaph\TypeArray\TypeArray;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class DispositionSearchResultMapperTest extends MockeryTestCase
{
    private DossierTypeSearchResultMapper&MockInterface $baseMapper;
    private DispositionRepository&MockInterface $repository;
    private DispositionSearchResultMapper $mapper;

    public function setUp(): void
    {
        $this->baseMapper = \Mockery::mock(DossierTypeSearchResultMapper::class);
        $this->repository = \Mockery::mock(DispositionRepository::class);

        $this->mapper = new DispositionSearchResultMapper(
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
            ->with($hit, $this->repository, ElasticDocumentType::DISPOSITION)
            ->andReturn($expectedResult);

        $this->assertEquals(
            $expectedResult,
            $this->mapper->map($hit),
        );
    }
}
