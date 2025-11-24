<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Result\Dossier\WooDecision;

use MinVWS\TypeArray\TypeArray;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Domain\Search\Index\ElasticDocumentType;
use Shared\Domain\Search\Index\Schema\ElasticField;
use Shared\Domain\Search\Result\Dossier\DossierSearchResultBaseMapper;
use Shared\Domain\Search\Result\Dossier\WooDecision\WooDecisionSearchResultMapper;
use Shared\Domain\Search\Result\ResultEntryInterface;
use Shared\Service\Security\ApplicationMode\ApplicationMode;
use Shared\Tests\Unit\UnitTestCase;

class WooDecisionSearchResultMapperTest extends UnitTestCase
{
    private DossierSearchResultBaseMapper&MockInterface $baseMapper;
    private WooDecisionRepository&MockInterface $repository;
    private WooDecisionSearchResultMapper $mapper;

    protected function setUp(): void
    {
        $this->baseMapper = \Mockery::mock(DossierSearchResultBaseMapper::class);
        $this->repository = \Mockery::mock(WooDecisionRepository::class);

        $this->mapper = new WooDecisionSearchResultMapper(
            $this->baseMapper,
            $this->repository,
        );
    }

    public function testSupports(): void
    {
        self::assertTrue($this->mapper->supports(ElasticDocumentType::WOO_DECISION));
        self::assertFalse($this->mapper->supports(ElasticDocumentType::COVENANT));
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
                [ElasticField::TITLE->value, ElasticField::SUMMARY->value],
                ApplicationMode::ADMIN,
            )
            ->andReturn($expectedResult);

        $this->assertEquals(
            $expectedResult,
            $this->mapper->map($hit, ApplicationMode::ADMIN),
        );
    }
}
