<?php

declare(strict_types=1);

namespace App\Service\Search\Result;

use App\Citation;
use App\Domain\Publication\Dossier\Type\WooDecision\DecisionType;
use App\Service\Search\Model\Aggregation;
use App\Service\Search\Model\AggregationBucketEntry;
use App\Service\Search\Model\FacetKey;
use Jaytaph\TypeArray\TypeArray;
use Symfony\Contracts\Translation\TranslatorInterface;

class AggregationMapper
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * @param iterable<string,TypeArray> $buckets
     */
    public function map(string $name, iterable $buckets): Aggregation
    {
        $entries = [];
        foreach ($buckets as $bucket) {
            $key = $bucket->getString('[key]');

            if ($this->shouldSkip($name, $key)) {
                continue;
            }

            $entries[] = new AggregationBucketEntry(
                $key,
                $bucket->getInt('[doc_count]'),
                $this->getDisplayValue($name, $key)
            );
        }

        return new Aggregation($name, $entries);
    }

    private function getDisplayValue(string $facetKey, string $value): string
    {
        $value = trim($value);

        return match ($facetKey) {
            FacetKey::TYPE->value => $this->translator->trans('public.documents.type.' . $value),
            FacetKey::GROUNDS->value => trim($value . ' ' . Citation::toClassification($value)),
            FacetKey::SOURCE->value => $this->translator->trans('public.documents.file_type.' . $value),
            FacetKey::JUDGEMENT->value => DecisionType::from($value)->trans($this->translator),
            default => $value === '' ? 'none' : $value,
        };
    }

    private function shouldSkip(string $facetKey, string $value): bool
    {
        // Special case: the 'ground' value 'dubbel' should be excluded from the facet
        if ($facetKey === FacetKey::GROUNDS->value && $value === Citation::DUBBEL) {
            return true;
        }

        return false;
    }
}
