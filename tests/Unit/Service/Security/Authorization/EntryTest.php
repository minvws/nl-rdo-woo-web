<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Security\Authorization;

use App\Service\Security\Authorization\Entry;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class EntryTest extends MockeryTestCase
{
    public function testEmptyEntry(): void
    {
        $entry = Entry::createFrom([]);

        $this->assertEquals([], $entry->getFilters());
        $this->assertEquals([], $entry->getPermissions());
        $this->assertEquals([], $entry->getRoles());
        $this->assertEquals('', $entry->getPrefix());
    }

    public function testCorrectEntry(): void
    {
        $entry = Entry::createFrom([
            'prefix' => 'prefix',
            'roles' => ['ROLE_TEST'],
            'permissions' => ['create' => true],
            'filters' => ['filter1' => true],
        ]);

        $this->assertEquals(['filter1' => true], $entry->getFilters());
        $this->assertEquals(['create' => true], $entry->getPermissions());
        $this->assertEquals(['ROLE_TEST'], $entry->getRoles());
        $this->assertEquals('prefix', $entry->getPrefix());
    }
}
