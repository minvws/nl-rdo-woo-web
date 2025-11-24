<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index\Rollover;

use Shared\Domain\Search\Index\ElasticIndex\ElasticIndexDetails;

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
