<?php

declare(strict_types=1);

namespace App\Service\Search\Model;

class Aggregation
{
    protected string $name;
    /** @var AggregationBucketEntry[] */
    protected array $entries;

    /**
     * @param AggregationBucketEntry[] $entries
     */
    public function __construct(string $name, array $entries)
    {
        $this->name = $name;
        $this->entries = $entries;
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
