<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Search\Result;

use App\Service\Search\Model\Aggregation;
use App\Service\Search\Model\FacetKey;
use App\Service\Search\Result\Result;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class ResultTest extends MockeryTestCase
{
    public function testGetAggregationReturnsMatchingAggregation(): void
    {
        $aggregationA = \Mockery::mock(Aggregation::class);
        $aggregationA->shouldReceive('getName')->andReturn(FacetKey::TYPE->value);

        $aggregationB = \Mockery::mock(Aggregation::class);
        $aggregationB->shouldReceive('getName')->andReturn(FacetKey::DATE->value);

        $result = new Result();
        $result->setAggregations([
            FacetKey::TYPE->value => $aggregationA,
            FacetKey::DATE->value => $aggregationB,
        ]);

        self::assertEquals(
            $aggregationB,
            $result->getAggregation(FacetKey::DATE),
        );
    }

    public function testGetAggregationReturnsNullForNoMatchingAggregation(): void
    {
        $aggregation = \Mockery::mock(Aggregation::class);
        $aggregation->shouldReceive('getName')->andReturn(FacetKey::TYPE->value);

        $result = new Result();
        $result->setAggregations([
            FacetKey::TYPE->value => $aggregation,
        ]);

        self::assertNull(
            $result->getAggregation(FacetKey::DATE),
        );
    }
}
