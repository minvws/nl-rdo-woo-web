<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\Rollover;

use App\Domain\Search\Index\ElasticIndex\ElasticIndexDetails;

readonly class RolloverDetails
{
    /**
     * @param array<array-key, MainTypeCount> $counts
     */
    public function __construct(
        public ElasticIndexDetails $index,
        public array $counts,
    ) {
    }
}
