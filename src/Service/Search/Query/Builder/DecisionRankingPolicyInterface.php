<?php

declare(strict_types=1);

namespace Shared\Service\Search\Query\Builder;

interface DecisionRankingPolicyInterface
{
    /**
     * @return array<string, float>
     */
    public function getWeights(): array;
}
