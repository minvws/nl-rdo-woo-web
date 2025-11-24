<?php

declare(strict_types=1);

namespace Shared\Service\Search\Query\Dsl;

use Erichard\ElasticQueryBuilder\Aggregation\AbstractAggregation;

/**
 * A global aggregation ignores the query/filters.
 * This makes it possible to define aggregation specific filters that differ from the main query.
 */
class GlobalAggregation extends AbstractAggregation
{
    protected function getType(): string
    {
        return 'global';
    }

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function build(): array
    {
        $data = [
            'global' => new \stdClass(),
        ];

        $this->buildAggregationsTo($data);

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildAggregation(): array
    {
        return [];
    }
}
