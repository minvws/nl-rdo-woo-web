<?php

declare(strict_types=1);

namespace Shared\Service\Search\Model;

class Suggestion
{
    /**
     * @param SuggestionEntry[] $entries
     */
    public function __construct(protected string $name, protected array $entries)
    {
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
