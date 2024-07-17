<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Filter;

use App\Domain\Search\Index\ElasticDocumentType;
use App\Service\Search\Model\Config;
use App\Service\Search\Query\Facet\Facet;
use App\Service\Search\Query\Query;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;

/**
 * This filter will match only ES documents of subtypes (see ElasticDocumentTypes).
 */
class SubTypesOnlyFilter implements FilterInterface
{
    public function __construct(
        private readonly FilterInterface $subFilter,
    ) {
    }

    public function addToQuery(Facet $facet, BoolQuery $query, Config $config, string $prefix = ''): void
    {
        if ($facet->isNotActive()) {
            return;
        }

        $query->addFilter(
            Query::Terms(
                field: 'type',
                values: ElasticDocumentType::getSubTypeValues(),
            ),
        );

        $this->subFilter->addToQuery($facet, $query, $config);
    }
}
