<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Inventory;

use PHPUnit\Framework\TestCase;
use Shared\Service\Inventory\PropertyChangeset;

class PropertyChangesetTest extends TestCase
{
    public function testEmptyChangeset(): void
    {
        $changeset = new PropertyChangeset();

        self::assertFalse($changeset->hasChanges());
        self::assertFalse($changeset->isChanged('foo'));
    }

    public function testChangesetAdd(): void
    {
        $changeset = new PropertyChangeset();
        $changeset->add('foo', true);
        $changeset->add('bar', false);
        $changeset->add('baz');

        self::assertTrue($changeset->hasChanges());
        self::assertTrue($changeset->isChanged('foo'));
        self::assertFalse($changeset->isChanged('bar'));
        self::assertTrue($changeset->isChanged('baz'));
    }

    public function testChangesetCompare(): void
    {
        $changeset = new PropertyChangeset();
        $changeset->compare('foo', 1, 1);
        $changeset->compare('bar', 1, 2);
        $changeset->compare('baz', 1, '1');

        self::assertTrue($changeset->hasChanges());
        self::assertFalse($changeset->isChanged('foo'));
        self::assertTrue($changeset->isChanged('bar'));
        self::assertTrue($changeset->isChanged('baz'));
    }

    public function testChangesetOverwritesKeys(): void
    {
        $changeset = new PropertyChangeset();
        $changeset->add('foo', true);

        self::assertTrue($changeset->hasChanges());
        self::assertTrue($changeset->isChanged('foo'));

        $changeset->add('foo', false);

        self::assertFalse($changeset->hasChanges());
        self::assertFalse($changeset->isChanged('foo'));
    }
}
