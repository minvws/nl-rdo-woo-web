<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Service\Search\Query\Definition;

use PHPUnit\Framework\Attributes\Group;
use Shared\Domain\Search\Query\Facet\Input\FacetInputFactory;
use Shared\Domain\Search\Query\SearchParameters;
use Shared\Service\Search\Query\Definition\SearchAllQueryDefinition;
use Shared\Service\Search\Query\Sort\SortField;
use Shared\Service\Search\Query\Sort\SortOrder;
use Shared\Tests\Integration\SharedWebTestCase;

#[Group('search')]
final class SearchAllQueryDefinitionTest extends SharedWebTestCase
{
    use QueryDefinitionTestTrait;

    public function testElasticQueryBuiltFromDefinition(): void
    {
        $facetInputFactory = self::getContainer()->get(FacetInputFactory::class);

        $searchParameters = new SearchParameters(
            $facetInputFactory->create(),
            limit: 10,
            offset: 20,
            query: 'foo',
            sortField: SortField::DECISION_DATE,
            sortOrder: SortOrder::DESC,
        );

        $this->matchDefinitionToSnapshot(SearchAllQueryDefinition::class, $searchParameters);
    }
}
