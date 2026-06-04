<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Search\Query\Builder;

use Erichard\ElasticQueryBuilder\Contracts\QueryInterface;
use Erichard\ElasticQueryBuilder\QueryBuilder;
use Mockery;
use Mockery\MockInterface;
use Shared\Service\Search\Query\Builder\DecisionRankingPolicyInterface;
use Shared\Service\Search\Query\Builder\DecisionRankingQueryBuilder;
use Shared\Service\Search\Query\Exception\InvalidQueryConfigurationException;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;

final class DecisionRankingQueryBuilderTest extends UnitTestCase
{
    private DecisionRankingPolicyInterface&MockInterface $policy;

    protected function setUp(): void
    {
        $this->policy = Mockery::mock(DecisionRankingPolicyInterface::class);
    }

    public function testWrapAddsFunctionScoreWithAllDecisionWeights(): void
    {
        $queryBuilder = new QueryBuilder();
        $queryBuilder->setQuery(new class implements QueryInterface {
            /**
             * @return array<string, mixed>
             */
            public function build(): array
            {
                return ['match_all' => new stdClass()];
            }
        });

        $this->policy->expects('getWeights')->andReturn([
            'public' => 1.0,
            'partial_public' => 1.0,
            'already_public' => 0.8,
            'not_public' => 0.6,
            'nothing_found' => 0.6,
        ]);

        $builder = new DecisionRankingQueryBuilder($this->policy);
        $builder->wrap($queryBuilder);

        $built = $queryBuilder->getQuery()?->build();

        self::assertIsArray($built);
        self::assertIsArray($built['function_score']);
        self::assertSame('multiply', $built['function_score']['score_mode']);
        self::assertSame('multiply', $built['function_score']['boost_mode']);
        $fsQuery = $built['function_score']['query'] ?? [];
        self::assertIsArray($fsQuery);
        self::assertArrayHasKey('match_all', $fsQuery);
        self::assertSame(
            [
                [
                    'filter' => ['term' => ['decision' => 'public']],
                    'weight' => 1.0,
                ],
                [
                    'filter' => ['term' => ['decision' => 'partial_public']],
                    'weight' => 1.0,
                ],
                [
                    'filter' => ['term' => ['decision' => 'already_public']],
                    'weight' => 0.8,
                ],
                [
                    'filter' => ['term' => ['decision' => 'not_public']],
                    'weight' => 0.6,
                ],
                [
                    'filter' => ['term' => ['decision' => 'nothing_found']],
                    'weight' => 0.6,
                ],
                [
                    'filter' => [
                        'bool' => [
                            'must_not' => [
                                'exists' => ['field' => 'decision'],
                            ],
                        ],
                    ],
                    'weight' => 1.0,
                ],
            ],
            $built['function_score']['functions'],
        );
    }

    public function testWrapFailsWhenQueryBuilderHasNoQuery(): void
    {
        $builder = new DecisionRankingQueryBuilder($this->policy);

        $this->expectException(InvalidQueryConfigurationException::class);
        $this->expectExceptionMessage('QueryBuilder has no query configured for decision ranking.');

        $builder->wrap(new QueryBuilder());
    }
}
