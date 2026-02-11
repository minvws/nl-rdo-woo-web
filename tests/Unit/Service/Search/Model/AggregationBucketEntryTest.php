<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Search\Model;

use Mockery;
use PHPUnit\Framework\TestCase;
use Shared\Domain\Search\Query\Facet\DisplayValue\UntranslatedStringFacetDisplayValue;
use Shared\Domain\Search\Query\SearchParameters;
use Shared\Service\Search\Model\AggregationBucketEntry;
use Symfony\Component\HttpFoundation\ParameterBag;

class AggregationBucketEntryTest extends TestCase
{
    public function testGetters(): void
    {
        $subEntry = Mockery::mock(AggregationBucketEntry::class);

        $entry = new AggregationBucketEntry(
            $key = 'foo',
            $count = 123,
            $value = UntranslatedStringFacetDisplayValue::fromString('bar'),
            $searchParams = Mockery::mock(SearchParameters::class),
            $searchParamsWithout = Mockery::mock(SearchParameters::class),
            $subEntries = [$subEntry]
        );

        self::assertEquals($key, $entry->getKey());
        self::assertEquals($count, $entry->getCount());
        self::assertEquals($value, $entry->getDisplayValue());
        self::assertEquals($subEntries, $entry->getSubEntries());

        $searchParams->expects('getQueryParameters')->andReturn(new ParameterBag(['foo' => 'bar']));
        self::assertEquals(['foo' => 'bar'], $entry->getQueryParams());

        $searchParamsWithout->expects('getQueryParameters')->andReturn(new ParameterBag(['bar' => 'foo']));
        self::assertEquals(['bar' => 'foo'], $entry->getQueryParamsWithout());
    }
}
