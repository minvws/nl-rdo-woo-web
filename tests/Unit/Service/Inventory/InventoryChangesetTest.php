<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Inventory;

use App\Exception\ProcessInventoryException;
use App\Service\Inventory\DocumentNumber;
use App\Service\Inventory\InventoryChangeset;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class InventoryChangesetTest extends MockeryTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testIsEmpty(): void
    {
        $changeset = new InventoryChangeset();
        self::assertTrue($changeset->hasChanges());

        $documentNumber = DocumentNumber::fromString('test', 'x', '123a');
        $changeset->markAsAdded($documentNumber);

        self::assertFalse($changeset->hasChanges());
    }

    public function testHandlingOfAdded(): void
    {
        $documentNumber = DocumentNumber::fromString('test', 'x', '123a');

        $changeset = new InventoryChangeset();
        $changeset->markAsAdded($documentNumber);

        self::assertEquals(InventoryChangeset::ADDED, $changeset->getStatus($documentNumber));
        self::assertEquals(
            [
                InventoryChangeset::ADDED => 1,
                InventoryChangeset::UPDATED => 0,
                InventoryChangeset::DELETED => 0,
                InventoryChangeset::UNCHANGED => 0,
            ],
            $changeset->getCounts()
        );
        self::assertEquals(
            [$documentNumber->getValue() => InventoryChangeset::ADDED],
            $changeset->getAll(),
        );
    }

    public function testHandlingOfUpdated(): void
    {
        $documentNumber = DocumentNumber::fromString('test', 'x', '123a');

        $changeset = new InventoryChangeset();
        $changeset->markAsUpdated($documentNumber);

        self::assertEquals(InventoryChangeset::UPDATED, $changeset->getStatus($documentNumber));
        self::assertEquals(
            [
                InventoryChangeset::ADDED => 0,
                InventoryChangeset::UPDATED => 1,
                InventoryChangeset::DELETED => 0,
                InventoryChangeset::UNCHANGED => 0,
            ],
            $changeset->getCounts()
        );
        self::assertEquals(
            [$documentNumber->getValue() => InventoryChangeset::UPDATED],
            $changeset->getAll(),
        );
    }

    public function testHandlingOfDeleted(): void
    {
        $documentNumber = DocumentNumber::fromString('test', 'x', '123a');

        $changeset = new InventoryChangeset();
        $changeset->markAsDeleted($documentNumber->getValue());

        self::assertEquals(InventoryChangeset::DELETED, $changeset->getStatus($documentNumber));
        self::assertEquals([$documentNumber->getValue()], $changeset->getDeleted());
        self::assertEquals(
            [
                InventoryChangeset::ADDED => 0,
                InventoryChangeset::UPDATED => 0,
                InventoryChangeset::DELETED => 1,
                InventoryChangeset::UNCHANGED => 0,
            ],
            $changeset->getCounts(),
        );
        self::assertEquals(
            [$documentNumber->getValue() => InventoryChangeset::DELETED],
            $changeset->getAll(),
        );
    }

    public function testHandlingOfUnchanged(): void
    {
        $documentNumber = DocumentNumber::fromString('test', 'x', '123a');

        $changeset = new InventoryChangeset();
        $changeset->markAsUnchanged($documentNumber);

        self::assertEquals(InventoryChangeset::UNCHANGED, $changeset->getStatus($documentNumber));
        self::assertEquals(
            [
                InventoryChangeset::ADDED => 0,
                InventoryChangeset::UPDATED => 0,
                InventoryChangeset::DELETED => 0,
                InventoryChangeset::UNCHANGED => 1,
            ],
            $changeset->getCounts(),
        );
        self::assertEquals(
            [$documentNumber->getValue() => InventoryChangeset::UNCHANGED],
            $changeset->getAll(),
        );
        self::assertTrue($changeset->hasChanges());
    }

    public function testDuplicateDocumentNumbersThrowAnException(): void
    {
        $changeset = new InventoryChangeset();

        $documentNr = DocumentNumber::fromString('test', 'x', '123a');
        $duplicateDocumentNr = DocumentNumber::fromString('test', 'x', '123a');

        $expectedException = ProcessInventoryException::forDuplicateDocumentNr($duplicateDocumentNr->getValue());

        $changeset->markAsAdded($documentNr);

        $this->expectExceptionObject($expectedException);
        $changeset->markAsAdded($duplicateDocumentNr);

        $this->expectExceptionObject($expectedException);
        $changeset->markAsUpdated($duplicateDocumentNr);

        $this->expectExceptionObject($expectedException);
        $changeset->markAsDeleted($duplicateDocumentNr->getValue());

        $this->expectExceptionObject($expectedException);
        $changeset->markAsUnchanged($duplicateDocumentNr);

        self::assertEquals([], $changeset->getAll());
    }
}
