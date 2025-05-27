<?php

declare(strict_types=1);

namespace App\Service\Search\Result;

use App\Domain\Search\Query\SearchParameters;
use App\Domain\Search\Result\ResultEntryInterface;
use App\Service\Search\Model\Aggregation;
use App\Service\Search\Model\FacetKey;
use App\Service\Search\Model\Suggestion;
use App\Service\Search\Query\Sort\ViewModel\SortItems;
use Knp\Component\Pager\Pagination\AbstractPagination;
use Knp\Component\Pager\Pagination\PaginationInterface;

/**
 * This class is the result that is returned after a search has been performed.
 *
 * @SuppressWarnings("PHPMD.TooManyFields")
 */
final class Result
{
    /**
     * Offset used for the search.
     */
    protected int $offset = 0;

    /**
     * Limit used for the search.
     */
    protected int $limit = 10;

    /**
     * Not null when pagination is enabled.
     *
     * @var PaginationInterface<int,AbstractPagination>|null
     */
    protected ?PaginationInterface $paginator = null;

    /**
     * Total of unique dossiers found.
     */
    protected int $dossierCount = 0;

    /**
     * Total number of documents found without a date (will be null if already filtered on documents without date).
     */
    protected ?int $documentCountWithoutDate = null;

    /**
     * True when the document count without date message should be displayed.
     */
    protected bool $displayWithoutDateMessage = false;

    /**
     * Time it has taken to search (in ms).
     */
    protected int $timeTaken = 0;

    /**
     * Actual search results (limited per page, so max 10).
     *
     * @var ResultEntryInterface[]
     */
    protected array $entries = [];

    /**
     * True when the search has failed.
     */
    protected bool $failed = false;

    /**
     * Error message when the search has failed.
     */
    protected string $message = '';

    /**
     * Type of documents that have been found (see SearchType enum).
     */
    protected string $type;

    /**
     * Aggregations found.
     *
     * @var Aggregation[]
     */
    protected array $aggregations = [];

    /**
     * Suggestions found.
     *
     * @var Suggestion[]
     */
    protected array $suggestions = [];

    /**
     * Actual query used to search.
     *
     * @var mixed[]
     */
    protected array $query;

    /**
     * Total number of result items.
     */
    private int $resultCount;

    private SortItems $sortItems;

    private SearchParameters $searchParameters;

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
     * @return ResultEntryInterface[]
     */
    public function getEntries(): array
    {
        return $this->entries;
    }

    /**
     * @param ResultEntryInterface[] $entries
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
     * @return Aggregation[]
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
     * @param Aggregation[] $aggregations
     */
    public function setAggregations(array $aggregations): self
    {
        $this->aggregations = $aggregations;

        return $this;
    }

    /**
     * @return Suggestion[]
     */
    public function getSuggestions(): array
    {
        return $this->suggestions;
    }

    /**
     * @param Suggestion[] $suggestions
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
}
