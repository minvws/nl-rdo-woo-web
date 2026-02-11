<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Query;

use Shared\Domain\Search\Query\SearchResultType;
use Shared\Tests\Unit\UnitTestCase;

final class SearchResultTypeTest extends UnitTestCase
{
    public function testSearchResultType(): void
    {
        $this->assertMatchesObjectSnapshot(SearchResultType::cases());
    }

    public function testGetAllValues(): void
    {
        $this->assertMatchesSnapshot(SearchResultType::getAllValues());
    }
}
