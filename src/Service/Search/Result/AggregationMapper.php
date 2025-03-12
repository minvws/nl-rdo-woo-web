<?php

declare(strict_types=1);

namespace App\Service\Search\Result;

use App\Citation;
use App\Domain\Search\Index\Schema\ElasticField;
use App\Domain\Search\Query\Facet\FacetDefinitions;
use App\Domain\Search\Query\Facet\Input\FacetInputFactory;
use App\Domain\Search\Query\SearchParameters;
use App\Service\Search\Model\Aggregation;
use App\Service\Search\Model\AggregationBucketEntry;
use App\Service\Search\Model\FacetKey;
use Jaytaph\TypeArray\TypeArray;

readonly class AggregationMapper
{
    private const KEY = '[key]';
    private const DOC_COUNT = '[doc_count]';

    public function __construct(
        private FacetInputFactory $facetInputFactory,
        private FacetDefinitions $facetDefinitions,
    ) {
    }

    /**
     * @param iterable<array-key,TypeArray> $buckets
     */
    public function map(string $name, iterable $buckets, SearchParameters $searchParameters): Aggregation
    {
        $entries = [];

        if ($name === ElasticField::TOPLEVEL_TYPE->value) {
            return $this->mapTypeAggregation($searchParameters, $buckets);
        }

        $facetKey = FacetKey::from($name);
        $facet = $this->facetDefinitions->get($facetKey);

        foreach ($buckets as $bucketKey => $bucket) {
            $value = $bucket->getString(self::KEY);

            if ($this->shouldSkip($facetKey, $value)) {
                continue;
            }

            $entries[] = new AggregationBucketEntry(
                $value,
                $bucket->getInt(self::DOC_COUNT),
                $facet->getDisplayValue($bucketKey, $value),
                $searchParameters->withFacetInput(
                    $facetKey,
                    $this->facetInputFactory->createStringFacetInputForValue($facetKey, $value)
                ),
                $searchParameters,
            );
        }

        return new Aggregation($name, $entries);
    }

    private function shouldSkip(FacetKey $facetKey, string $value): bool
    {
        // Special case: the 'ground' value 'dubbel' should be excluded from the facet
        return $facetKey === FacetKey::GROUNDS && $value === Citation::DUBBEL;
    }

    /**
     * @param iterable<string,TypeArray> $buckets
     */
    private function mapTypeAggregation(SearchParameters $searchParameters, iterable $buckets): Aggregation
    {
        $facet = $this->facetDefinitions->get(FacetKey::TYPE);

        $entries = [];
        foreach ($buckets as $bucketKey => $bucket) {
            $key = $bucket->getString(self::KEY);
            $allKeys = [
                $key,
                $key . '.publication',
            ];

            $subEntries = [];
            $publicationCount = $bucket->getInt('[publication][doc_count]');
            if ($publicationCount > 0) {
                $combinedKey = $key . '.publication';
                $subEntries[] = new AggregationBucketEntry(
                    $combinedKey,
                    $publicationCount,
                    $facet->getDisplayValue($bucketKey, $combinedKey),
                    $searchParameters->withFacetInput(
                        FacetKey::TYPE,
                        $this->facetInputFactory->createStringFacetInputForValue(FacetKey::TYPE, $key)
                    ),
                    $searchParameters,
                );
            }

            foreach ($bucket->getIterable('[' . ElasticField::SUBLEVEL_TYPE->value . '][buckets]') as $subBucketKey => $subBucket) {
                $subKey = $subBucket->getString(self::KEY);
                $combinedKey = $key . '.' . $subKey;
                $allKeys[] = $combinedKey;
                $subEntries[] = new AggregationBucketEntry(
                    $combinedKey,
                    $subBucket->getInt(self::DOC_COUNT),
                    $facet->getDisplayValue($subBucketKey, $combinedKey),
                    $searchParameters->withFacetInput(
                        FacetKey::TYPE,
                        $this->facetInputFactory->createStringFacetInputForValue(FacetKey::TYPE, $subKey)
                    ),
                    $searchParameters,
                );
            }

            $entries[] = new AggregationBucketEntry(
                $key,
                $bucket->getInt(self::DOC_COUNT),
                $facet->getDisplayValue($bucketKey, $key),
                $searchParameters->withFacetInput(
                    FacetKey::TYPE,
                    $this->facetInputFactory->createStringFacetInputForValue(FacetKey::TYPE, ...$allKeys)
                ),
                $searchParameters,
                $subEntries,
            );
        }

        return new Aggregation(FacetKey::TYPE->value, $entries);
    }
}
