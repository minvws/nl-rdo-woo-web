<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Result;

use MinVWS\TypeArray\TypeArray;
use Mockery\MockInterface;
use Shared\Domain\Search\Index\ElasticDocumentType;
use Shared\Domain\Search\Result\ResultEntryInterface;
use Shared\Domain\Search\Result\ResultFactory;
use Shared\Domain\Search\Result\SearchResultException;
use Shared\Domain\Search\Result\SearchResultMapperInterface;
use Shared\Service\Security\ApplicationMode\ApplicationMode;
use Shared\Tests\Unit\UnitTestCase;

class ResultFactoryTest extends UnitTestCase
{
    private SearchResultMapperInterface&MockInterface $firstMapper;
    private SearchResultMapperInterface&MockInterface $secondMapper;
    private ResultFactory $factory;

    protected function setUp(): void
    {
        $this->firstMapper = \Mockery::mock(SearchResultMapperInterface::class);
        $this->secondMapper = \Mockery::mock(SearchResultMapperInterface::class);

        $this->factory = new ResultFactory([$this->firstMapper, $this->secondMapper]);
    }

    public function testMapIsForwardedToFirstSupportingMapper(): void
    {
        $hit = \Mockery::mock(TypeArray::class);
        $hit->shouldReceive('getString')->with('[fields][type][0]')->andReturn(ElasticDocumentType::WOO_DECISION->value);

        $mode = ApplicationMode::ADMIN;

        $result = \Mockery::mock(ResultEntryInterface::class);
        $this->firstMapper->expects('supports')->with(ElasticDocumentType::WOO_DECISION)->andReturnFalse();
        $this->secondMapper->expects('supports')->with(ElasticDocumentType::WOO_DECISION)->andReturnTrue();
        $this->secondMapper->expects('map')->with($hit, $mode)->andReturn($result);

        $this->assertSame($result, $this->factory->map($hit, $mode));
    }

    public function testMapThrowsExceptionWhenNoMapperSupportsTheHit(): void
    {
        $hit = \Mockery::mock(TypeArray::class);
        $hit->shouldReceive('getString')->with('[fields][type][0]')->andReturn(ElasticDocumentType::WOO_DECISION->value);

        $this->firstMapper->expects('supports')->with(ElasticDocumentType::WOO_DECISION)->andReturnFalse();
        $this->secondMapper->expects('supports')->with(ElasticDocumentType::WOO_DECISION)->andReturnFalse();

        $this->expectException(SearchResultException::class);
        $this->factory->map($hit, ApplicationMode::PUBLIC);
    }
}
