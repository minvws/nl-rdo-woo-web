<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Search\Query\Sort\ViewModel;

use App\Service\Search\Query\Sort\SortException;
use App\Service\Search\Query\Sort\ViewModel\SortItem;
use App\Service\Search\Query\Sort\ViewModel\SortItems;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class SortItemsTest extends TestCase
{
    private SortItem&MockInterface $sortItemA;
    private SortItem&MockInterface $sortItemB;
    private SortItems $sortItems;

    public function setUp(): void
    {
        $this->sortItemA = \Mockery::mock(SortItem::class);
        $this->sortItemB = \Mockery::mock(SortItem::class);

        $this->sortItems = new SortItems($this->sortItemA, $this->sortItemB);

        parent::setUp();
    }

    public function testGetActive(): void
    {
        $this->sortItemA->expects('isActive')->andReturnFalse();
        $this->sortItemB->expects('isActive')->andReturnTrue();

        self::assertSame(
            $this->sortItemB,
            $this->sortItems->getActive(),
        );
    }

    public function testGetActiveThrowsExceptionWhenNotFound(): void
    {
        $this->sortItemA->expects('isActive')->andReturnFalse();
        $this->sortItemB->expects('isActive')->andReturnFalse();

        $this->expectException(SortException::class);
        $this->sortItems->getActive();
    }

    public function testIterator(): void
    {
        self::assertEquals(
            [$this->sortItemA, $this->sortItemB],
            iterator_to_array($this->sortItems)
        );
    }
}
