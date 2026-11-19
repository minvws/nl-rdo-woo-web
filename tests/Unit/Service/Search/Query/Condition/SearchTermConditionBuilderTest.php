<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Search\Query\Condition;

use Erichard\ElasticQueryBuilder\Query\BoolQuery;
use Mockery;
use Shared\Domain\Search\Query\Facet\FacetList;
use Shared\Domain\Search\Query\Facet\Input\FacetInputCollection;
use Shared\Domain\Search\Query\SearchParameters;
use Shared\Service\Search\Query\Condition\AdminSearchTermConditionBuilder;
use Shared\Service\Search\Query\Condition\SearchTermConditionBuilder;
use Shared\Tests\Unit\UnitTestCase;
use Webmozart\Assert\Assert;

use function json_encode;

final class SearchTermConditionBuilderTest extends UnitTestCase
{
    public function testSubjectQueryMatchesTitleAndSummaryOfStandaloneTypes(): void
    {
        $query = new SearchTermConditionBuilder()->createSubjectQuery($this->createSearchParameters('Onderwerp X'));

        self::assertEquals(
            [
                'bool' => [
                    'should' => [
                        ['simple_query_string' => ['query' => 'Onderwerp X', 'fields' => ['title'], 'default_operator' => 'and', 'boost' => 5]],
                        ['simple_query_string' => ['query' => 'Onderwerp X', 'fields' => ['summary'], 'default_operator' => 'and', 'boost' => 4]],
                        [
                            'bool' => [
                                'should' => [
                                    [
                                        'nested' => [
                                            'path' => 'content_tree',
                                            'query' => [
                                                'bool' => [
                                                    'should' => [
                                                        [
                                                            'simple_query_string' => [
                                                                'query' => 'Onderwerp X',
                                                                'fields' => ['content_tree.title'],
                                                                'default_operator' => 'and',
                                                                'boost' => 3,
                                                            ],
                                                        ],
                                                        [
                                                            'simple_query_string' => [
                                                                'query' => 'Onderwerp X',
                                                                'fields' => ['content_tree.body'],
                                                                'default_operator' => 'and',
                                                                'boost' => 2,
                                                            ],
                                                        ],
                                                    ],
                                                    'minimum_should_match' => 1,
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                                'filter' => [
                                    ['term' => ['has_visible_content_tree' => ['value' => true]]],
                                ],
                                'minimum_should_match' => 1,
                            ],
                        ],
                    ],
                    'filter' => [
                        ['terms' => ['type' => ['subject']]],
                    ],
                    'minimum_should_match' => 1,
                ],
            ],
            $query->build(),
        );
    }

    public function testSubjectQueryIsAddedToThePublicSearchTermConditions(): void
    {
        $searchParameters = $this->createSearchParameters('Onderwerp X');
        $builder = new SearchTermConditionBuilder();

        $query = new BoolQuery();
        $builder->applyToQuery(new FacetList([]), $searchParameters, $query);

        $builtQuery = $query->build();
        Assert::isArray($builtQuery);
        $rootBoolQuery = $builtQuery['bool'] ?? null;
        Assert::isArray($rootBoolQuery);
        $shouldQueries = $rootBoolQuery['should'] ?? null;
        Assert::isArray($shouldQueries);

        self::assertContains($builder->createSubjectQuery($searchParameters)->build(), $shouldQueries);
    }

    public function testSubjectQueryIsNotAddedToTheAdminSearchTermConditions(): void
    {
        $query = new BoolQuery();
        new AdminSearchTermConditionBuilder()->applyToQuery(new FacetList([]), $this->createSearchParameters('Onderwerp X'), $query);

        self::assertStringNotContainsString('"subject"', (string) json_encode($query->build()));
    }

    private function createSearchParameters(string $query): SearchParameters
    {
        return new SearchParameters(
            facetInputs: Mockery::mock(FacetInputCollection::class),
            query: $query,
        );
    }
}
