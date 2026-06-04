<?php

declare(strict_types=1);

namespace Shared\Service\Search\Model;

class Suggestion
{
    /**
     * @param array<array-key, SuggestionEntry> $entries
     */
    public function __construct(protected string $name, protected array $entries)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array<array-key, SuggestionEntry>
     */
    public function getEntries(): array
    {
        return $this->entries;
    }
}
