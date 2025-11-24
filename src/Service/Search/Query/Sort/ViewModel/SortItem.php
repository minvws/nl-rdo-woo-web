<?php

declare(strict_types=1);

namespace Shared\Service\Search\Query\Sort\ViewModel;

use Shared\Domain\Search\Query\SearchParameters;
use Shared\Service\Search\Query\Sort\SortField;
use Shared\Service\Search\Query\Sort\SortOrder;

class SortItem
{
    public function __construct(
        public SearchParameters $searchParameters,
        public bool $active,
        public SortField $field,
        public SortOrder $order,
        public bool $hideSortOrder,
    ) {
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function showSortOrder(): bool
    {
        return ! $this->hideSortOrder;
    }
}
