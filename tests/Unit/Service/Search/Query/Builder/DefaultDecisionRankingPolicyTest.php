<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Search\Query\Builder;

use InvalidArgumentException;
use Shared\Service\Search\Query\Builder\DefaultDecisionRankingPolicy;
use Shared\Tests\Unit\UnitTestCase;

final class DefaultDecisionRankingPolicyTest extends UnitTestCase
{
    public function testReturnsConfiguredWeights(): void
    {
        $policy = new DefaultDecisionRankingPolicy([
            'public' => 1.0,
            'partial_public' => 1.0,
            'already_public' => 0.8,
            'not_public' => 0.6,
            'nothing_found' => 0.6,
        ]);

        self::assertSame([
            'public' => 1.0,
            'partial_public' => 1.0,
            'already_public' => 0.8,
            'not_public' => 0.6,
            'nothing_found' => 0.6,
        ], $policy->getWeights());
    }

    public function testThrowsWhenConfiguredWeightsDoNotMatchDecisionSet(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new DefaultDecisionRankingPolicy([
            'public' => 1.0,
        ]);
    }

    public function testThrowsWhenConfiguredWeightsContainUnknownDecisions(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new DefaultDecisionRankingPolicy([
            'public' => 1.0,
            'partial_public' => 1.0,
            'already_public' => 0.8,
            'not_public' => 0.6,
            'nothing_found' => 0.6,
            'unknown' => 0.5,
        ]);
    }

    public function testThrowsWhenConfiguredWeightsContainNonPositiveValues(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new DefaultDecisionRankingPolicy([
            'public' => 1.0,
            'partial_public' => 0.0,
            'already_public' => 0.8,
            'not_public' => 0.6,
            'nothing_found' => 0.6,
        ]);
    }
}
