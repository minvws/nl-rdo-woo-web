<?php

declare(strict_types=1);

namespace App\Service\Elastic\Model;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class RolloverDetails
{
    public function __construct(
        public readonly Index $index,
        public readonly int $expectedDossierCount,
        public readonly int $expectedDocumentCount,
        public readonly int $expectedPageCount,
        public readonly int $elasticsearchDossierCount,
        public readonly int $elasticsearchDocumentCount,
        public readonly int $elasticsearchPageCount,
    ) {
    }

    public function getDossierPercentage(): float
    {
        return $this->calculatePercentage($this->expectedDossierCount, $this->elasticsearchDossierCount);
    }

    public function getDocumentPercentage(): float
    {
        return $this->calculatePercentage($this->expectedDocumentCount, $this->elasticsearchDocumentCount);
    }

    public function getPagePercentage(): float
    {
        return $this->calculatePercentage($this->expectedPageCount, $this->elasticsearchPageCount);
    }

    protected function calculatePercentage(int $expected, int $actual): float
    {
        return $expected == 0 ? 0.00 : round(($actual / $expected) * 100, 2);
    }
}
