<?php

declare(strict_types=1);

namespace App\Service\Search\Model;

class AggregationBucketEntry
{
    protected string $key;
    protected int $count;

    public function __construct(string $key, int $count)
    {
        $this->key = $key;
        $this->count = $count;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getCount(): int
    {
        return $this->count;
    }
}
