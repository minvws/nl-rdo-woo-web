<?php

declare(strict_types=1);

namespace App\Service\Elastic;

use Erichard\ElasticQueryBuilder\Query\QueryStringQuery;

class SimpleQueryStringQuery extends QueryStringQuery
{
    /**
     * @return mixed[]
     */
    public function build(): array
    {
        $ret = parent::build();

        return [
            'simple_query_string' => $ret['query_string'],
        ];
    }
}
