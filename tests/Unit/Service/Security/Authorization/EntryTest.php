<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Security\Authorization;

use Shared\Service\Security\Authorization\Entry;
use Shared\Tests\Unit\UnitTestCase;

class EntryTest extends UnitTestCase
{
    public function testEmptyEntry(): void
    {
        $entry = Entry::createFrom([]);

        self::assertEquals([], $entry->getFilters());
        self::assertEquals([], $entry->getPermissions());
        self::assertEquals([], $entry->getRoles());
        self::assertEquals('', $entry->getPrefix());
    }

    public function testCorrectEntry(): void
    {
        $entry = Entry::createFrom([
            'prefix' => 'prefix',
            'roles' => ['ROLE_TEST'],
            'permissions' => ['create' => true],
            'filters' => ['filter1' => true],
        ]);

        self::assertEquals(['filter1' => true], $entry->getFilters());
        self::assertEquals(['create' => true], $entry->getPermissions());
        self::assertEquals(['ROLE_TEST'], $entry->getRoles());
        self::assertEquals('prefix', $entry->getPrefix());
    }
}
