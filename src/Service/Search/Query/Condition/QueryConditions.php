<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Condition;

use App\Service\Search\Model\Config;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;

interface QueryConditions
{
    public function applyToQuery(Config $config, BoolQuery $query): void;
}
