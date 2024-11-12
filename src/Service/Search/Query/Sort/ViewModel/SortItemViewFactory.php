<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Sort\ViewModel;

use App\Domain\Search\Query\SearchParameters;
use App\Domain\Search\Query\SearchType;
use App\Service\Search\Query\Sort\SortField;
use App\Service\Search\Query\Sort\SortOrder;

class SortItemViewFactory
{
    public function make(SearchParameters $searchParameters): SortItems
    {
        $sortItems = [
            $this->createSortItem(SortField::SCORE, SortOrder::DESC, $searchParameters, true),
        ];

        foreach ($this->getAvailableSortFields($searchParameters) as $sortField) {
            $sortItems[] = $this->createSortItem($sortField, SortOrder::DESC, $searchParameters);
            $sortItems[] = $this->createSortItem($sortField, SortOrder::ASC, $searchParameters);
        }

        return new SortItems(...$sortItems);
    }

    /**
     * @return SortField[]
     */
    private function getAvailableSortFields(SearchParameters $searchParameters): array
    {
        $sortFields = [
            SortField::PUBLICATION_DATE,
        ];

        if ($searchParameters->searchType === SearchType::DOSSIER) {
            $sortFields[] = SortField::DECISION_DATE;
        }

        return $sortFields;
    }

    private function createSortItem(
        SortField $field,
        SortOrder $order,
        SearchParameters $searchParameters,
        bool $hideSortOrder = false,
    ): SortItem {
        return new SortItem(
            $searchParameters->withSort($field, $order),
            $field === $searchParameters->sortField && $order === $searchParameters->sortOrder,
            $field,
            $order,
            $hideSortOrder,
        );
    }
}
