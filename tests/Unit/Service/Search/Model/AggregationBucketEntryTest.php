<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Search\Model;

use App\Domain\Search\Query\Facet\DisplayValue\UntranslatedStringFacetDisplayValue;
use App\Domain\Search\Query\SearchParameters;
use App\Service\Search\Model\AggregationBucketEntry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;

class AggregationBucketEntryTest extends TestCase
{
    public function testGetters(): void
    {
        $subEntry = \Mockery::mock(AggregationBucketEntry::class);

        $entry = new AggregationBucketEntry(
            $key = 'foo',
            $count = 123,
            $value = UntranslatedStringFacetDisplayValue::fromString('bar'),
            $searchParams = \Mockery::mock(SearchParameters::class),
            $searchParamsWithout = \Mockery::mock(SearchParameters::class),
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
