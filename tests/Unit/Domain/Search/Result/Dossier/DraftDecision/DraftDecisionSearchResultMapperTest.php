<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Result\Dossier\DraftDecision;

use MinVWS\TypeArray\TypeArray;
use Mockery;
use Mockery\MockInterface;
use Shared\ApplicationId;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionRepository;
use Shared\Domain\Search\Index\ElasticDocumentType;
use Shared\Domain\Search\Index\Schema\ElasticField;
use Shared\Domain\Search\Result\Dossier\DossierSearchResultBaseMapper;
use Shared\Domain\Search\Result\Dossier\DraftDecision\DraftDecisionSearchResultMapper;
use Shared\Domain\Search\Result\ResultEntryInterface;
use Shared\Tests\Unit\UnitTestCase;

class DraftDecisionSearchResultMapperTest extends UnitTestCase
{
    private DossierSearchResultBaseMapper&MockInterface $baseMapper;
    private DraftDecisionRepository&MockInterface $repository;
    private DraftDecisionSearchResultMapper $mapper;

    protected function setUp(): void
    {
        $this->baseMapper = Mockery::mock(DossierSearchResultBaseMapper::class);
        $this->repository = Mockery::mock(DraftDecisionRepository::class);

        $this->mapper = new DraftDecisionSearchResultMapper(
            $this->baseMapper,
            $this->repository,
        );
    }

    public function testSupports(): void
    {
        self::assertTrue($this->mapper->supports(ElasticDocumentType::DRAFT_DECISION));
        self::assertFalse($this->mapper->supports(ElasticDocumentType::COVENANT));
    }

    public function testMapForwardsToBaseMapper(): void
    {
        $hit = Mockery::mock(TypeArray::class);

        $expectedResult = Mockery::mock(ResultEntryInterface::class);

        $this->baseMapper
            ->expects('map')
            ->with(
                $hit,
                $this->repository,
                ElasticDocumentType::DRAFT_DECISION,
                [ElasticField::TITLE->value, ElasticField::SUMMARY->value],
                ApplicationId::ADMIN,
            )
            ->andReturn($expectedResult);

        $this->assertEquals(
            $expectedResult,
            $this->mapper->map($hit, ApplicationId::ADMIN),
        );
    }
}
