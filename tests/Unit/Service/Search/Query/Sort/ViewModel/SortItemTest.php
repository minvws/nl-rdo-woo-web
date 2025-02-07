<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Search\Query\Sort\ViewModel;

use App\Domain\Search\Query\SearchParameters;
use App\Service\Search\Query\Sort\SortField;
use App\Service\Search\Query\Sort\SortOrder;
use App\Service\Search\Query\Sort\ViewModel\SortItem;
use PHPUnit\Framework\TestCase;

class SortItemTest extends TestCase
{
    public function testIsActiveReturnsTrue(): void
    {
        $sortItem = new SortItem(
            \Mockery::mock(SearchParameters::class),
            true,
            SortField::SCORE,
            SortOrder::ASC,
            false,
        );

        self::assertTrue($sortItem->isActive());
    }

    public function testIsActiveReturnsFalse(): void
    {
        $sortItem = new SortItem(
            \Mockery::mock(SearchParameters::class),
            false,
            SortField::SCORE,
            SortOrder::ASC,
            false,
        );

        self::assertFalse($sortItem->isActive());
    }

    public function testShowSortOrderReturnsTrue(): void
    {
        $sortItem = new SortItem(
            \Mockery::mock(SearchParameters::class),
            true,
            SortField::SCORE,
            SortOrder::ASC,
            false,
        );

        self::assertTrue($sortItem->showSortOrder());
    }

    public function testShowSortOrderReturnsFalse(): void
    {
        $sortItem = new SortItem(
            \Mockery::mock(SearchParameters::class),
            false,
            SortField::SCORE,
            SortOrder::ASC,
            true,
        );

        self::assertFalse($sortItem->showSortOrder());
    }
}
