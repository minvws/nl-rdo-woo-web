<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Sort\ViewModel;

use App\Service\Search\Query\Sort\SortException;

class SortItems implements \IteratorAggregate
{
    /**
     * @var SortItem[]
     */
    private readonly array $sortItems;

    public function __construct(SortItem ...$sortItems)
    {
        $this->sortItems = $sortItems;
    }

    public function getActive(): SortItem
    {
        foreach ($this->sortItems as $sortItem) {
            if ($sortItem->isActive()) {
                return $sortItem;
            }
        }

        throw SortException::forActiveSortNotFound();
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->sortItems);
    }
}
