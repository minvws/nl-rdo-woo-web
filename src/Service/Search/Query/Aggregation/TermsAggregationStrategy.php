<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Aggregation;

class TermsAggregationStrategy implements AggregationStrategyInterface
{
    public function __construct(
        protected string $tagName,
        protected string $fieldName,
        protected int $maxCount,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getQuery(): array
    {
        return [
            $this->tagName => [
                'terms' => [
                    'field' => $this->fieldName,
                    'size' => $this->maxCount,
                    'order' => [
                        '_count' => 'desc',
                    ],
                    'min_doc_count' => 0,
                ],
            ],
        ];
    }
}
