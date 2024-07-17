<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Result\Dossier\ComplaintJudgement;

use App\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementRepository;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Result\Dossier\ComplaintJudgement\ComplaintJudgementSearchResultMapper;
use App\Domain\Search\Result\Dossier\DossierSearchResultBaseMapper;
use App\Domain\Search\Result\ResultEntryInterface;
use Jaytaph\TypeArray\TypeArray;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class ComplaintJudgementSearchResultMapperTest extends MockeryTestCase
{
    private DossierSearchResultBaseMapper&MockInterface $baseMapper;
    private ComplaintJudgementRepository&MockInterface $repository;
    private ComplaintJudgementSearchResultMapper $mapper;

    public function setUp(): void
    {
        $this->baseMapper = \Mockery::mock(DossierSearchResultBaseMapper::class);
        $this->repository = \Mockery::mock(ComplaintJudgementRepository::class);

        $this->mapper = new ComplaintJudgementSearchResultMapper(
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
            ->with($hit, $this->repository, ElasticDocumentType::COMPLAINT_JUDGEMENT)
            ->andReturn($expectedResult);

        $this->assertEquals(
            $expectedResult,
            $this->mapper->map($hit),
        );
    }
}
