<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Inventory;

use Shared\Exception\ProcessInventoryException;
use Shared\Service\Inventory\DocumentNumber;
use Shared\Service\Inventory\InventoryChangeset;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentMatter;

class InventoryChangesetTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testIsEmpty(): void
    {
        $changeset = new InventoryChangeset();
        self::assertTrue($changeset->hasNoChanges());

        $documentNumber = DocumentNumber::fromPrefixMatterAndInput('test', DocumentMatter::create('x'), '123a');
        $changeset->markAsAdded($documentNumber);

        self::assertFalse($changeset->hasNoChanges());
    }

    public function testHandlingOfAdded(): void
    {
        $documentNumber = DocumentNumber::fromPrefixMatterAndInput('test', DocumentMatter::create('x'), '123a');

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
            $changeset->getCounts(),
        );
        self::assertEquals(
            [$documentNumber->toString() => InventoryChangeset::ADDED],
            $changeset->getAll(),
        );
    }

    public function testHandlingOfUpdated(): void
    {
        $documentNumber = DocumentNumber::fromPrefixMatterAndInput('test', DocumentMatter::create('x'), '123a');

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
            $changeset->getCounts(),
        );
        self::assertEquals(
            [$documentNumber->toString() => InventoryChangeset::UPDATED],
            $changeset->getAll(),
        );
    }

    public function testHandlingOfDeleted(): void
    {
        $documentNumber = DocumentNumber::fromPrefixMatterAndInput('test', DocumentMatter::create('x'), '123a');

        $changeset = new InventoryChangeset();
        $changeset->markAsDeleted($documentNumber->toString());

        self::assertEquals(InventoryChangeset::DELETED, $changeset->getStatus($documentNumber));
        self::assertEquals([$documentNumber->toString()], $changeset->getDeleted());
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
            [$documentNumber->toString() => InventoryChangeset::DELETED],
            $changeset->getAll(),
        );
    }

    public function testHandlingOfUnchanged(): void
    {
        $documentNumber = DocumentNumber::fromPrefixMatterAndInput('test', DocumentMatter::create('x'), '123a');

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
            [$documentNumber->toString() => InventoryChangeset::UNCHANGED],
            $changeset->getAll(),
        );
        self::assertTrue($changeset->hasNoChanges());
    }

    public function testDuplicateDocumentNumbersThrowAnException(): void
    {
        $changeset = new InventoryChangeset();

        $documentNumber = DocumentNumber::fromPrefixMatterAndInput('test', DocumentMatter::create('x'), '123a');
        $duplicateDocumentNumber = DocumentNumber::fromPrefixMatterAndInput('test', DocumentMatter::create('x'), '123a');

        $expectedException = ProcessInventoryException::forDuplicateDocumentNumber($duplicateDocumentNumber->toString());

        $changeset->markAsAdded($documentNumber);

        $this->expectExceptionObject($expectedException);
        $changeset->markAsAdded($duplicateDocumentNumber);

        $this->expectExceptionObject($expectedException);
        $changeset->markAsUpdated($duplicateDocumentNumber);

        $this->expectExceptionObject($expectedException);
        $changeset->markAsDeleted($duplicateDocumentNumber->toString());

        $this->expectExceptionObject($expectedException);
        $changeset->markAsUnchanged($duplicateDocumentNumber);

        self::assertEquals([], $changeset->getAll());
    }

    public function testGetResultingTotalDocumentCount(): void
    {
        $documentNumberA = DocumentNumber::fromPrefixMatterAndInput('test', DocumentMatter::create('x'), '123a');
        $documentNumberB = DocumentNumber::fromPrefixMatterAndInput('test', DocumentMatter::create('x'), '123b');
        $documentNumberC = DocumentNumber::fromPrefixMatterAndInput('test', DocumentMatter::create('x'), '123c');
        $documentNumberD = DocumentNumber::fromPrefixMatterAndInput('test', DocumentMatter::create('x'), '123d');

        $changeset = new InventoryChangeset();
        $changeset->markAsAdded($documentNumberA);
        $changeset->markAsDeleted($documentNumberB->toString());
        $changeset->markAsUnchanged($documentNumberC);
        $changeset->markAsUpdated($documentNumberD);

        // One added + one unchanged + one updated and don't count the deleted one = 3
        self::assertEquals(3, $changeset->getResultingTotalDocumentCount());
    }
}
