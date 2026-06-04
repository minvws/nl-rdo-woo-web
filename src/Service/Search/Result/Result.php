<?php

declare(strict_types=1);

namespace Shared\Service\Search\Result;

use Knp\Component\Pager\Pagination\AbstractPagination;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Shared\Domain\Search\Query\SearchParameters;
use Shared\Domain\Search\Result\ResultEntryInterface;
use Shared\Service\Search\Model\Aggregation;
use Shared\Service\Search\Model\FacetKey;
use Shared\Service\Search\Model\Suggestion;
use Shared\Service\Search\Query\Sort\ViewModel\SortItems;

/**
 * This class is the result that is returned after a search has been performed.
 */
final class Result
{
    public const string DEFAULT_ROUTE_NAME = 'app_search';

    /**
     * Offset used for the search.
     */
    private int $offset = 0;

    /**
     * Limit used for the search.
     */
    private int $limit = 10;

    /**
     * Not null when pagination is enabled.
     *
     * @var PaginationInterface<int,AbstractPagination>|null
     */
    private ?PaginationInterface $paginator = null;

    /**
     * Total of unique dossiers found.
     */
    private int $dossierCount = 0;

    /**
     * Total number of documents found without a date (will be null if already filtered on documents without date).
     */
    private ?int $documentCountWithoutDate = null;

    /**
     * True when the document count without date message should be displayed.
     */
    private bool $displayWithoutDateMessage = false;

    /**
     * Time it has taken to search (in ms).
     */
    private int $timeTaken = 0;

    /**
     * Actual search results (limited per page, so max 10).
     *
     * @var array<array-key, ResultEntryInterface>
     */
    private array $entries = [];

    /**
     * True when the search has failed.
     */
    private bool $failed = false;

    /**
     * Error message when the search has failed.
     */
    private string $message = '';

    /**
     * Type of documents that have been found (see SearchType enum).
     */
    private string $type;

    /**
     * Aggregations found.
     *
     * @var array<array-key, Aggregation>
     */
    private array $aggregations = [];

    /**
     * Suggestions found.
     *
     * @var array<array-key, Suggestion>
     */
    private array $suggestions = [];

    /**
     * Actual query used to search.
     *
     * @var array<array-key, mixed>
     */
    private array $query;

    /**
     * Total number of result items.
     */
    private int $resultCount;

    private SortItems $sortItems;
    private SearchParameters $searchParameters;
    private string $routeName = self::DEFAULT_ROUTE_NAME;

    /**
     * @var array<string, mixed>
     */
    private array $routeParameters = [];

    public static function create(): self
    {
        return new self();
    }

    public function getSortItems(): SortItems
    {
        return $this->sortItems;
    }

    public function setSortItems(SortItems $sortItems): self
    {
        $this->sortItems = $sortItems;

        return $this;
    }

    public function getSearchParameters(): SearchParameters
    {
        return $this->searchParameters;
    }

    public function setSearchParameters(SearchParameters $searchParameters): self
    {
        $this->searchParameters = $searchParameters;

        return $this;
    }

    public function getResultCount(): int
    {
        return $this->resultCount;
    }

    public function setResultCount(int $resultCount): self
    {
        $this->resultCount = $resultCount;

        return $this;
    }

    public function getDossierCount(): int
    {
        return $this->dossierCount;
    }

    public function setDossierCount(int $dossierCount): self
    {
        $this->dossierCount = $dossierCount;

        return $this;
    }

    public function getDocumentCountWithoutDate(): ?int
    {
        return $this->documentCountWithoutDate;
    }

    public function setDocumentCountWithoutDate(?int $documentCountWithoutDate): self
    {
        $this->documentCountWithoutDate = $documentCountWithoutDate;

        return $this;
    }

    public function getDisplayWithoutDateMessage(): bool
    {
        return $this->displayWithoutDateMessage;
    }

    public function setDisplayWithoutDateMessage(bool $displayWithoutDateMessage): self
    {
        $this->displayWithoutDateMessage = $displayWithoutDateMessage;

        return $this;
    }

    public function getTimeTaken(): int
    {
        return $this->timeTaken;
    }

    public function setTimeTaken(int $timeTaken): self
    {
        $this->timeTaken = $timeTaken;

        return $this;
    }

    /**
     * @return array<array-key, ResultEntryInterface>
     */
    public function getEntries(): array
    {
        return $this->entries;
    }

    /**
     * @param array<array-key, ResultEntryInterface> $entries
     */
    public function setEntries(array $entries): self
    {
        $this->entries = $entries;

        return $this;
    }

    public function hasFailed(): bool
    {
        return $this->failed;
    }

    public function setFailed(bool $failed): self
    {
        $this->failed = $failed;

        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return array<array-key, Aggregation>
     */
    public function getAggregations(): array
    {
        return $this->aggregations;
    }

    public function getAggregation(FacetKey $key): ?Aggregation
    {
        foreach ($this->aggregations as $aggregation) {
            if ($aggregation->getName() === $key->value) {
                return $aggregation;
            }
        }

        return null;
    }

    /**
     * @param array<array-key, Aggregation> $aggregations
     */
    public function setAggregations(array $aggregations): self
    {
        $this->aggregations = $aggregations;

        return $this;
    }

    /**
     * @return array<array-key, Suggestion>
     */
    public function getSuggestions(): array
    {
        return $this->suggestions;
    }

    /**
     * @param array<array-key, Suggestion> $suggestions
     */
    public function setSuggestions(array $suggestions): self
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
        return $this->resultCount === 0;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getQuery(): array
    {
        return $this->query;
    }

    /**
     * @param array<array-key, mixed> $query
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

    public function setOffset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setLimit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function isPaginationEnabled(): bool
    {
        return $this->paginator !== null;
    }

    /**
     * @param PaginationInterface<int,AbstractPagination> $paginator
     */
    public function setPagination(PaginationInterface $paginator): void
    {
        $this->paginator = $paginator;
    }

    /**
     * @return PaginationInterface<int,AbstractPagination>|null
     */
    public function pagination(): ?PaginationInterface
    {
        return $this->paginator;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getRouteName(): string
    {
        return $this->routeName;
    }

    public function setRouteName(string $routeName): self
    {
        $this->routeName = $routeName;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRouteParameters(): array
    {
        return $this->routeParameters;
    }

    /**
     * @param array<string, mixed> $routeParameters
     */
    public function setRouteParameters(array $routeParameters): self
    {
        $this->routeParameters = $routeParameters;

        return $this;
    }
}
