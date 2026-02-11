<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Search\Query\Sort\ViewModel;

use Mockery;
use PHPUnit\Framework\TestCase;
use Shared\Domain\Search\Query\SearchParameters;
use Shared\Service\Search\Query\Sort\SortField;
use Shared\Service\Search\Query\Sort\SortOrder;
use Shared\Service\Search\Query\Sort\ViewModel\SortItem;

class SortItemTest extends TestCase
{
    public function testIsActiveReturnsTrue(): void
    {
        $sortItem = new SortItem(
            Mockery::mock(SearchParameters::class),
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
            Mockery::mock(SearchParameters::class),
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
            Mockery::mock(SearchParameters::class),
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
            Mockery::mock(SearchParameters::class),
            false,
            SortField::SCORE,
            SortOrder::ASC,
            true,
        );

        self::assertFalse($sortItem->showSortOrder());
    }
}
