<?php

declare(strict_types=1);

namespace App\Service\Search\Result;

use App\Citation;
use App\Domain\Publication\Dossier\Type\WooDecision\DecisionType;
use App\Domain\Search\Index\ElasticField;
use App\Domain\Search\Query\SearchParameters;
use App\Domain\Search\Result\FacetValue\AbbreviatedValue;
use App\Domain\Search\Result\FacetValue\FacetValueInterface;
use App\Domain\Search\Result\FacetValue\TranslatedFacetValue;
use App\Service\Search\Model\Aggregation;
use App\Service\Search\Model\AggregationBucketEntry;
use App\Service\Search\Model\FacetKey;
use App\Service\Search\Query\Facet\Input\FacetInputFactory;
use App\SourceType;
use Jaytaph\TypeArray\TypeArray;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
readonly class AggregationMapper
{
    private const KEY = '[key]';
    private const DOC_COUNT = '[doc_count]';

    public function __construct(
        private TranslatorInterface $translator,
        private FacetInputFactory $facetInputFactory,
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

        foreach ($buckets as $bucket) {
            $key = $bucket->getString(self::KEY);

            if ($this->shouldSkip($facetKey, $key)) {
                continue;
            }

            $entries[] = new AggregationBucketEntry(
                $key,
                $bucket->getInt(self::DOC_COUNT),
                $this->getDisplayValue($name, $key),
                $searchParameters->withFacetInput(
                    $facetKey,
                    $this->facetInputFactory->createStringFacetInputForValue($facetKey, $key)
                ),
                $searchParameters,
            );
        }

        return new Aggregation($name, $entries);
    }

    private function getDisplayValue(string $facetKey, string $value): string|FacetValueInterface
    {
        $value = trim($value);

        return match ($facetKey) {
            FacetKey::TYPE->value => TranslatedFacetValue::create($this->translator, $facetKey, $value),
            FacetKey::GROUNDS->value => trim($value . ' ' . Citation::toClassification($value)),
            FacetKey::SOURCE->value => SourceType::create($value)->trans($this->translator),
            FacetKey::JUDGEMENT->value => DecisionType::from($value)->trans($this->translator),
            FacetKey::DEPARTMENT->value => AbbreviatedValue::fromString($value),
            default => $value === '' ? 'none' : $value,
        };
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
        $entries = [];
        foreach ($buckets as $bucket) {
            $key = $bucket->getString(self::KEY);
            $allKeys = [
                $key,
                $key . '.publication',
            ];

            $subEntries = [];
            $publicationCount = $bucket->getInt('[publication][doc_count]');
            if ($publicationCount > 0) {
                $subEntries[] = new AggregationBucketEntry(
                    'publication',
                    $publicationCount,
                    'public.search.type.publication',
                    $searchParameters->withFacetInput(
                        FacetKey::TYPE,
                        $this->facetInputFactory->createStringFacetInputForValue(FacetKey::TYPE, $key)
                    ),
                    $searchParameters,
                );
            }

            foreach ($bucket->getIterable('[' . ElasticField::SUBLEVEL_TYPE->value . '][buckets]') as $subBucket) {
                $subKey = $subBucket->getString(self::KEY);
                $allKeys[] = $key . '.' . $subKey;
                $subEntries[] = new AggregationBucketEntry(
                    $subKey,
                    $subBucket->getInt(self::DOC_COUNT),
                    'public.search.type.' . $subKey,
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
                $this->getDisplayValue(FacetKey::TYPE->value, $key),
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
