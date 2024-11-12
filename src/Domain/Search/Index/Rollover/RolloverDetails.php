<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\Rollover;

use App\Domain\Search\Index\ElasticIndex\ElasticIndexDetails;

readonly class RolloverDetails
{
    public function __construct(
        public ElasticIndexDetails $index,
        public int $expectedDossierCount,
        public int $expectedDocCount,
        public int $expectedPageCount,
        public int $actualDossierCount,
        public int $actualDocCount,
        public int $actualPageCount,
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
