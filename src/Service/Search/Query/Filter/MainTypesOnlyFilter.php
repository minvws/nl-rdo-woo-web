<?php

declare(strict_types=1);

namespace Shared\Service\Search\Query\Filter;

use Erichard\ElasticQueryBuilder\Query\BoolQuery;
use Shared\Domain\Search\Index\ElasticDocumentType;
use Shared\Domain\Search\Index\Schema\ElasticField;
use Shared\Domain\Search\Index\Schema\ElasticNestedField;
use Shared\Domain\Search\Query\Facet\Facet;
use Shared\Domain\Search\Query\SearchParameters;
use Shared\Service\Search\Query\Dsl\Query;

/**
 * This filter will match only ES documents of main types (see ElasticDocumentTypes)
 * Excluding matches on subtypes, will also not match on main type docs nested within subtype docs!
 */
readonly class MainTypesOnlyFilter implements FilterInterface
{
    public function __construct(
        private FilterInterface $subFilter,
    ) {
    }

    public function addToQuery(
        Facet $facet,
        BoolQuery $query,
        SearchParameters $searchParameters,
        ?ElasticNestedField $nestedPath = null,
    ): void {
        if ($facet->isNotActive()) {
            return;
        }

        $query->addFilter(
            Query::Terms(
                field: ElasticField::TYPE->value,
                values: ElasticDocumentType::getMainTypeValues(),
            ),
        );

        $this->subFilter->addToQuery($facet, $query, $searchParameters);
    }
}
