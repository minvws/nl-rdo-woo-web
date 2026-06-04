<?php

declare(strict_types=1);

namespace Shared\Service\Search\Query\Dsl;

use Erichard\ElasticQueryBuilder\Contracts\QueryInterface;

final readonly class FunctionScoreQuery implements QueryInterface
{
    private const string DEFAULT_SCORE_MODE = 'multiply';
    private const string DEFAULT_BOOST_MODE = 'multiply';

    /**
     * @param array<array-key, array<string, mixed>> $functions
     */
    public function __construct(
        private QueryInterface $query,
        private array $functions,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        return [
            'function_score' => [
                'query' => $this->query->build(),
                'functions' => $this->functions,
                'score_mode' => self::DEFAULT_SCORE_MODE,
                'boost_mode' => self::DEFAULT_BOOST_MODE,
            ],
        ];
    }
}
