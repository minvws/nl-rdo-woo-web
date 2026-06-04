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

    public function testElasticQueryUsesDecisionFunctionScoreRanking(): void
    {
        $searchData = $this->getDefinitionSearchData(
            SearchAllQueryDefinition::class,
            $this->createSearchParameters(),
        );

        self::assertArrayHasKey('body', $searchData);
        $body = $searchData['body'];
        self::assertIsArray($body);
        self::assertArrayHasKey('query', $body);
        $query = $body['query'];
        self::assertIsArray($query);
        self::assertArrayHasKey('function_score', $query);

        $functionScore = $query['function_score'];
        self::assertIsArray($functionScore);

        self::assertSame('multiply', $functionScore['score_mode']);
        self::assertSame('multiply', $functionScore['boost_mode']);
        $functions = $functionScore['functions'] ?? [];
        self::assertIsArray($functions);
        self::assertCount(6, $functions);
        $fsQuery = $functionScore['query'] ?? [];
        self::assertIsArray($fsQuery);
        self::assertArrayHasKey('bool', $fsQuery);
        self::assertArrayHasKey('aggs', $body);
        $aggs = $body['aggs'];
        self::assertIsArray($aggs);
        self::assertArrayHasKey('judgement', $aggs);
    }

    public function testElasticQueryBuiltFromDefinition(): void
    {
        $this->matchDefinitionToSnapshot(SearchAllQueryDefinition::class, $this->createSearchParameters());
    }

    private function createSearchParameters(): SearchParameters
    {
        $facetInputFactory = self::fromContainer(FacetInputFactory::class);

        return new SearchParameters(
            $facetInputFactory->create(),
            limit: 10,
            offset: 20,
            query: 'foo',
            sortField: SortField::DECISION_DATE,
            sortOrder: SortOrder::DESC,
        );
    }
}
