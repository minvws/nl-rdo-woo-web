<?php

declare(strict_types=1);

namespace Shared\Service\Search\Query\Dsl;

use Erichard\ElasticQueryBuilder\Query\BoolQuery;

class MatchAllQuery extends BoolQuery
{
    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function build(): array
    {
        return ['match_all' => new \stdClass()];
    }
}
