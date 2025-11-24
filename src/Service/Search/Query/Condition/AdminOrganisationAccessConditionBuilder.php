<?php

declare(strict_types=1);

namespace Shared\Service\Search\Query\Condition;

use Erichard\ElasticQueryBuilder\Query\BoolQuery;
use Shared\Domain\Search\Index\Schema\ElasticField;
use Shared\Domain\Search\Query\Facet\FacetList;
use Shared\Domain\Search\Query\SearchParameters;
use Shared\Service\Search\Query\Dsl\Query;
use Shared\Service\Security\Authorization\AuthorizationMatrix;

readonly class AdminOrganisationAccessConditionBuilder implements QueryConditionBuilderInterface
{
    public function __construct(
        private AuthorizationMatrix $authorizationMatrix,
    ) {
    }

    public function applyToQuery(FacetList $facetList, SearchParameters $searchParameters, BoolQuery $query): void
    {
        $organisationId = $this->authorizationMatrix->getActiveOrganisation()->getId()->toRfc4122();

        $query->addFilter(
            Query::term(
                field: ElasticField::ORGANISATION_IDS->value . '.keyword',
                value: $organisationId,
            ),
        );
    }
}
