<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Result\Dossier\Covenant;

use App\Domain\Publication\Dossier\Type\Covenant\CovenantRepository;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Result\Dossier\Covenant\CovenantSearchResultMapper;
use App\Domain\Search\Result\Dossier\DossierSearchResultBaseMapper;
use App\Domain\Search\Result\ResultEntryInterface;
use Jaytaph\TypeArray\TypeArray;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class CovenantSearchResultMapperTest extends MockeryTestCase
{
    private DossierSearchResultBaseMapper&MockInterface $baseMapper;
    private CovenantRepository&MockInterface $repository;
    private CovenantSearchResultMapper $mapper;

    public function setUp(): void
    {
        $this->baseMapper = \Mockery::mock(DossierSearchResultBaseMapper::class);
        $this->repository = \Mockery::mock(CovenantRepository::class);

        $this->mapper = new CovenantSearchResultMapper(
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
            ->with($hit, $this->repository, ElasticDocumentType::COVENANT)
            ->andReturn($expectedResult);

        $this->assertEquals(
            $expectedResult,
            $this->mapper->map($hit),
        );
    }
}
