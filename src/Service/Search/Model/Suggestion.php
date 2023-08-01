<?php

declare(strict_types=1);

namespace App\Service\Search\Model;

class Suggestion
{
    protected string $name;
    /** @var SuggestionEntry[] */
    protected array $entries;

    /**
     * @param SuggestionEntry[] $entries
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
     * @return SuggestionEntry[]
     */
    public function getEntries(): array
    {
        return $this->entries;
    }
}
