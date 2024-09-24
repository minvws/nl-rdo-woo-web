<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Query;

use App\Domain\Search\Query\SearchType;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Component\HttpFoundation\Request;

class SearchTypeTest extends MockeryTestCase
{
    public function testIsDossier(): void
    {
        self::assertFalse(SearchType::ALL->isDossier());
        self::assertFalse(SearchType::DOCUMENT->isDossier());
        self::assertTrue(SearchType::DOSSIER->isDossier());
    }

    public function testIsDocument(): void
    {
        self::assertFalse(SearchType::ALL->isDocument());
        self::assertTrue(SearchType::DOCUMENT->isDocument());
        self::assertFalse(SearchType::DOSSIER->isDocument());
    }

    public function testIsAll(): void
    {
        self::assertTrue(SearchType::ALL->isAll());
        self::assertFalse(SearchType::DOCUMENT->isAll());
        self::assertFalse(SearchType::DOSSIER->isAll());
    }

    public function testIsNotAll(): void
    {
        self::assertFalse(SearchType::ALL->isNotAll());
        self::assertTrue(SearchType::DOCUMENT->isNotAll());
        self::assertTrue(SearchType::DOSSIER->isNotAll());
    }

    public function testFromRequest(): void
    {
        $request = new Request(['type' => 'dossier']);

        self::assertTrue(
            SearchType::fromParameterBag($request->query)->isDossier()
        );
    }

    public function testFromRequestFallbackToDefault(): void
    {
        $request = new Request(['type' => 'foo']);

        self::assertTrue(
            SearchType::fromParameterBag($request->query)->isAll()
        );
    }
}
