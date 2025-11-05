<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Result\Dossier\Covenant;

use App\Domain\Publication\Dossier\Type\Covenant\CovenantRepository;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Index\Schema\ElasticField;
use App\Domain\Search\Result\Dossier\Covenant\CovenantSearchResultMapper;
use App\Domain\Search\Result\Dossier\DossierSearchResultBaseMapper;
use App\Domain\Search\Result\ResultEntryInterface;
use App\Service\Security\ApplicationMode\ApplicationMode;
use MinVWS\TypeArray\TypeArray;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class CovenantSearchResultMapperTest extends MockeryTestCase
{
    private DossierSearchResultBaseMapper&MockInterface $baseMapper;
    private CovenantRepository&MockInterface $repository;
    private CovenantSearchResultMapper $mapper;

    protected function setUp(): void
    {
        $this->baseMapper = \Mockery::mock(DossierSearchResultBaseMapper::class);
        $this->repository = \Mockery::mock(CovenantRepository::class);

        $this->mapper = new CovenantSearchResultMapper(
            $this->baseMapper,
            $this->repository,
        );
    }

    public function testSupports(): void
    {
        self::assertTrue($this->mapper->supports(ElasticDocumentType::COVENANT));
        self::assertFalse($this->mapper->supports(ElasticDocumentType::DISPOSITION));
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
                ElasticDocumentType::COVENANT,
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
