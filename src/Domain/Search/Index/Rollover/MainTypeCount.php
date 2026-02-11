<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index\Rollover;

use Shared\Domain\Search\Index\ElasticDocumentType;

use function round;

readonly class MainTypeCount
{
    /**
     * @param array<array-key, self> $subCounts
     */
    public function __construct(
        public ElasticDocumentType $type,
        public int $expected,
        public int $actual,
        public array $subCounts = [],
    ) {
    }

    public function getPercentage(): float
    {
        return $this->calculatePercentage($this->actual, $this->expected);
    }

    protected function calculatePercentage(int $actual, int $expected): float
    {
        if ($expected === 0 && $actual === 0) {
            return 100;
        }

        if ($expected === 0 && $actual > 0) {
            return 0;
        }

        return round(($actual / $expected) * 100, 2);
    }
}
