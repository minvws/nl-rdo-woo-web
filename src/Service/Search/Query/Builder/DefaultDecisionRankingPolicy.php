<?php

declare(strict_types=1);

namespace Shared\Service\Search\Query\Builder;

use InvalidArgumentException;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Decision\DecisionType;
use Shared\Service\EnumHelper;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

use function array_diff;
use function array_keys;
use function implode;
use function sprintf;

final readonly class DefaultDecisionRankingPolicy implements DecisionRankingPolicyInterface
{
    /**
     * @param array<string, float> $weights
     */
    public function __construct(
        #[Autowire(param: 'decision_ranking_weights')]
        private array $weights,
    ) {
        $this->validateWeights();
    }

    /**
     * @return array<string, float>
     */
    public function getWeights(): array
    {
        return $this->weights;
    }

    private function validateWeights(): void
    {
        $expectedKeys = EnumHelper::getStringValues(DecisionType::cases());
        $actualKeys = array_keys($this->weights);

        $missingKeys = array_diff($expectedKeys, $actualKeys);
        if ($missingKeys !== []) {
            throw new InvalidArgumentException(sprintf(
                'Missing decision ranking weights for: %s',
                implode(', ', $missingKeys),
            ));
        }

        $unknownKeys = array_diff($actualKeys, $expectedKeys);
        if ($unknownKeys !== []) {
            throw new InvalidArgumentException(sprintf(
                'Unknown decision ranking weights configured for: %s',
                implode(', ', $unknownKeys),
            ));
        }

        // Validate that all weights are positive numbers
        foreach ($this->weights as $weight) {
            if ($weight <= 0) {
                throw new InvalidArgumentException('All decision ranking weights must be positive numbers.');
            }
        }
    }
}
