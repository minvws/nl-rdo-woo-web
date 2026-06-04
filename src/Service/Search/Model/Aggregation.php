<?php

declare(strict_types=1);

namespace Shared\Service\Search\Model;

class Aggregation
{
    /**
     * @param array<array-key, AggregationBucketEntry> $entries
     */
    public function __construct(protected string $name, protected array $entries)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array<array-key, AggregationBucketEntry>
     */
    public function getEntries(): array
    {
        return $this->entries;
    }
}
