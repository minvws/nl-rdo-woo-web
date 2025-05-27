<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Filter;

use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Index\Schema\ElasticField;
use App\Domain\Search\Index\Schema\ElasticNestedField;
use App\Domain\Search\Query\Facet\Facet;
use App\Domain\Search\Query\SearchParameters;
use App\Service\Search\Query\Dsl\Query;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;

/**
 * This filter will match only ES documents of subtypes (see ElasticDocumentTypes).
 */
readonly class SubTypesOnlyFilter implements FilterInterface
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
                values: ElasticDocumentType::getSubTypeValues(),
            ),
        );

        $this->subFilter->addToQuery($facet, $query, $searchParameters);
    }
}
