<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Aggregation;

class NestedAggregationStrategy implements AggregationStrategyInterface
{
    /**
     * @param AggregationStrategyInterface[] $innerStrategies
     */
    public function __construct(
        protected string $tagName,
        protected string $path,
        protected iterable $innerStrategies,
    ) {
        foreach ($this->innerStrategies as $innerStrategy) {
            if (! $innerStrategy instanceof AggregationStrategyInterface) {
                throw new \TypeError('All elements of $innerStrategies must be instances of AggregationStrategyInterface');
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getQuery(): array
    {
        $aggs = [];
        foreach ($this->innerStrategies as $inner) {
            $innerQuery = $inner->getQuery();
            $aggs = array_merge_recursive($aggs, $innerQuery);
        }

        return [
            $this->tagName => [
                'nested' => [
                    'path' => $this->path,
                ],
                'aggs' => $aggs,
            ],
        ];
    }
}
