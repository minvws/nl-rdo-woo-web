<?php

declare(strict_types=1);

namespace App\Service\Search\Model;

class SuggestionEntry
{
    public function __construct(protected string $name, protected float $score, protected int $frequency)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getScore(): float
    {
        return $this->score;
    }

    public function getFrequency(): int
    {
        return $this->frequency;
    }
}
