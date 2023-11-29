<?php

declare(strict_types=1);

namespace App\Service\Elastic\Model;

class RolloverDetails
{
    public function __construct(
        public readonly Index $index,
        public readonly int $expectedDossierCount,
        public readonly int $expectedDocCount,
        public readonly int $expectedPageCount,
        public readonly int $actualDossierCount,
        public readonly int $actualDocCount,
        public readonly int $actualPageCount,
    ) {
    }

    public function getDossierPercentage(): float
    {
        return $this->calculatePercentage($this->expectedDossierCount, $this->actualDossierCount);
    }

    public function getDocumentPercentage(): float
    {
        return $this->calculatePercentage($this->expectedDocCount, $this->actualDocCount);
    }

    public function getPagePercentage(): float
    {
        return $this->calculatePercentage($this->expectedPageCount, $this->actualPageCount);
    }

    protected function calculatePercentage(int $expected, int $actual): float
    {
        return $expected === 0 ? 0.00 : round(($actual / $expected) * 100, 2);
    }
}
