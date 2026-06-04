<?php

declare(strict_types=1);

namespace Shared\Service\Search\Query\Builder;

use Erichard\ElasticQueryBuilder\Contracts\QueryInterface;
use Erichard\ElasticQueryBuilder\QueryBuilder;
use Shared\Domain\Search\Index\Schema\ElasticField;
use Shared\Service\Search\Query\Dsl\FunctionScoreQuery;
use Shared\Service\Search\Query\Exception\InvalidQueryConfigurationException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DecisionRankingQueryBuilder
{
    private const float NEUTRAL_WEIGHT = 1.0;

    public function __construct(
        #[Autowire(service: DefaultDecisionRankingPolicy::class)]
        private DecisionRankingPolicyInterface $policy,
    ) {
    }

    public function wrap(QueryBuilder $queryBuilder): void
    {
        $query = $queryBuilder->getQuery();
        if (! $query instanceof QueryInterface) {
            throw new InvalidQueryConfigurationException('QueryBuilder has no query configured for decision ranking.');
        }

        $queryBuilder->setQuery(
            new FunctionScoreQuery(
                query: $query,
                functions: $this->buildFunctions(),
            ),
        );
    }

    /**
     * @return array<array-key, array<string, mixed>>
     */
    private function buildFunctions(): array
    {
        $field = ElasticField::DECISION->value;
        $functions = [];

        foreach ($this->policy->getWeights() as $value => $weight) {
            $functions[] = [
                'filter' => [
                    'term' => [
                        $field => $value,
                    ],
                ],
                'weight' => $weight,
            ];
        }

        $functions[] = [
            'filter' => [
                'bool' => [
                    'must_not' => [
                        'exists' => [
                            'field' => $field,
                        ],
                    ],
                ],
            ],
            'weight' => self::NEUTRAL_WEIGHT,
        ];

        return $functions;
    }
}
