<?php

declare(strict_types=1);

namespace App\Service\Search\Model;

class Aggregation
{
    /**
     * @param AggregationBucketEntry[] $entries
     */
    public function __construct(protected string $name, protected array $entries)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return AggregationBucketEntry[]
     */
    public function getEntries(): array
    {
        return $this->entries;
    }
}
