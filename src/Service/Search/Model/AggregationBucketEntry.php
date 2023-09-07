<?php

declare(strict_types=1);

namespace App\Service\Search\Model;

class AggregationBucketEntry
{
    public function __construct(
        private readonly string $key,
        private readonly int $count,
        private readonly string $displayValue,
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getDisplayValue(): string
    {
        return $this->displayValue;
    }
}
