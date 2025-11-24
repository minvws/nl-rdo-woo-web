<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Result\Dossier\OtherPublication;

use MinVWS\TypeArray\TypeArray;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationRepository;
use Shared\Domain\Search\Index\ElasticDocumentType;
use Shared\Domain\Search\Index\Schema\ElasticField;
use Shared\Domain\Search\Result\Dossier\DossierSearchResultBaseMapper;
use Shared\Domain\Search\Result\Dossier\OtherPublication\OtherPublicationSearchResultMapper;
use Shared\Domain\Search\Result\ResultEntryInterface;
use Shared\Service\Security\ApplicationMode\ApplicationMode;
use Shared\Tests\Unit\UnitTestCase;

class OtherPublicationSearchResultMapperTest extends UnitTestCase
{
    private DossierSearchResultBaseMapper&MockInterface $baseMapper;
    private OtherPublicationRepository&MockInterface $repository;
    private OtherPublicationSearchResultMapper $mapper;

    protected function setUp(): void
    {
        $this->baseMapper = \Mockery::mock(DossierSearchResultBaseMapper::class);
        $this->repository = \Mockery::mock(OtherPublicationRepository::class);

        $this->mapper = new OtherPublicationSearchResultMapper(
            $this->baseMapper,
            $this->repository,
        );
    }

    public function testSupports(): void
    {
        self::assertTrue($this->mapper->supports(ElasticDocumentType::OTHER_PUBLICATION));
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
                ElasticDocumentType::OTHER_PUBLICATION,
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
