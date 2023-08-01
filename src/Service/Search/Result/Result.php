<?php

declare(strict_types=1);

namespace App\Service\Search\Result;

use App\Service\Search\Model\Aggregation;
use App\Service\Search\Model\Suggestion;
use Knp\Component\Pager\Pagination\AbstractPagination;
use Knp\Component\Pager\Pagination\PaginationInterface;

/**
 * This class is the result that is returned after a search has been performed.
 */
class Result
{
    // Seems like these should not be in here
    protected int $offset = 0;                  // Offset used for the search
    protected int $limit = 10;                  // Limit used for the search

    /** @var PaginationInterface<string, AbstractPagination>|null */
    protected ?PaginationInterface $paginator = null;   // not null when pagination is enabled

    protected int $documentCount = 0;           // Total number of documents found
    protected int $dossierCount = 0;            // Total of unique dossiers found
    protected int $timeTaken = 0;               // Time it has taken to search (in ms)
    /** @var array|ResultEntry[] */
    protected array $entries = [];              // Actual search results (limited per page, so max 10)
    protected bool $failed = false;             // True when the search has failed
    protected string $message = '';             // Error message when the search has failed
    protected string $type;                     // Type of documents that have been found (all, dossier, document, Config::TYPE_*)

    /** @var array|Aggregation[] */
    protected array $aggregations = [];         // Aggregations found
    /** @var array|Suggestion[] */
    protected array $suggestions = [];          // Suggestions found

    /** @var array|mixed[] */
    protected array $query;                     // Actual query used to search

    public static function create(): self
    {
        return new self();
    }

    public function getDocumentCount(): int
    {
        return $this->documentCount;
    }

    public function setDocumentCount(int $documentCount): Result
    {
        $this->documentCount = $documentCount;

        return $this;
    }

    public function getDossierCount(): int
    {
        return $this->dossierCount;
    }

    public function setDossierCount(int $dossierCount): Result
    {
        $this->dossierCount = $dossierCount;

        return $this;
    }

    public function getTimeTaken(): int
    {
        return $this->timeTaken;
    }

    public function setTimeTaken(int $timeTaken): Result
    {
        $this->timeTaken = $timeTaken;

        return $this;
    }

    /**
     * @return array|ResultEntry[]
     */
    public function getEntries(): array
    {
        return $this->entries;
    }

    /**
     * @param array|ResultEntry[] $entries
     */
    public function setEntries(array $entries): Result
    {
        $this->entries = $entries;

        return $this;
    }

    public function hasFailed(): bool
    {
        return $this->failed;
    }

    public function setFailed(bool $failed): Result
    {
        $this->failed = $failed;

        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): Result
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return Aggregation[]
     */
    public function getAggregations(): array
    {
        return $this->aggregations;
    }

    public function getAggregation(string $name): ?Aggregation
    {
        foreach ($this->aggregations as $aggregation) {
            if ($aggregation->getName() == $name) {
                return $aggregation;
            }
        }

        return null;
    }

    /**
     * @param Aggregation[] $aggregations
     */
    public function setAggregations(array $aggregations): Result
    {
        $this->aggregations = $aggregations;

        return $this;
    }

    /**
     * @return array|Suggestion[]
     */
    public function getSuggestions(): array
    {
        return $this->suggestions;
    }

    /**
     * @param array|Suggestion[] $suggestions
     */
    public function setSuggestions(array $suggestions): Result
    {
        $this->suggestions = $suggestions;

        return $this;
    }

    public function getSuggestion(string $name): ?Suggestion
    {
        foreach ($this->suggestions as $suggestion) {
            if ($suggestion->getName() == $name) {
                return $suggestion;
            }
        }

        return null;
    }

    public function isEmpty(): bool
    {
        return $this->documentCount === 0;
    }

    /**
     * @return mixed[]
     */
    public function getQuery(): array
    {
        return $this->query;
    }

    /**
     * @param mixed[] $query
     */
    public function setQuery(array $query): self
    {
        $this->query = $query;

        return $this;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function setOffset(int $offset): Result
    {
        $this->offset = $offset;

        return $this;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setLimit(int $limit): Result
    {
        $this->limit = $limit;

        return $this;
    }

    public function isPaginationEnabled(): bool
    {
        return $this->paginator !== null;
    }

    /**
     * @param PaginationInterface<string, AbstractPagination> $paginator
     */
    public function setPagination(PaginationInterface $paginator): void
    {
        $this->paginator = $paginator;
    }

    /**
     * @return PaginationInterface<string, AbstractPagination>|null
     */
    public function pagination(): ?PaginationInterface
    {
        return $this->paginator;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): Result
    {
        $this->type = $type;

        return $this;
    }
}
