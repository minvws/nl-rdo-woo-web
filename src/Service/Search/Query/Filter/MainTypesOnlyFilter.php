<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Filter;

use App\Domain\Search\Index\ElasticDocumentType;
use App\Service\Search\Model\Config;
use App\Service\Search\Query\Facet\Facet;
use App\Service\Search\Query\Query;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;

/**
 * This filter will match only ES documents of main types (see ElasticDocumentTypes)
 * Excluding matches on subtypes, will also not match on main type docs nested within subtype docs!
 */
class MainTypesOnlyFilter implements FilterInterface
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
                values: array_map(
                    static fn (ElasticDocumentType $type) => $type->value,
                    ElasticDocumentType::getMainTypes(),
                ),
            ),
        );

        $this->subFilter->addToQuery($facet, $query, $config);
    }
}
