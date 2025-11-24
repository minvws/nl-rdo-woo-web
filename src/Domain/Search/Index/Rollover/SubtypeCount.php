<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index\Rollover;

use Shared\Domain\Search\Index\ElasticDocumentType;

readonly class SubtypeCount extends MainTypeCount
{
    public function __construct(
        public ElasticDocumentType $type,
        public int $expected,
        public int $actual,
        public int $expectedPages,
        public int $actualPages,
    ) {
    }

    public function getPagesPercentage(): float
    {
        return $this->calculatePercentage($this->actualPages, $this->expectedPages);
    }
}
