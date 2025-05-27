<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Condition;

use App\Domain\Search\Index\Schema\ElasticField;
use App\Domain\Search\Query\Facet\FacetList;
use App\Domain\Search\Query\SearchParameters;
use App\Service\Search\Query\Dsl\Query;
use App\Service\Security\Authorization\AuthorizationMatrix;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;

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
