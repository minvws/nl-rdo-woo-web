<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\ViewModel;

use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReport;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportProcessRun;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ViewModel\ProductionReportStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Service\Inventory\InventoryChangeset;
use Shared\Tests\Unit\UnitTestCase;

class ProductionReportStatusTest extends UnitTestCase
{
    private WooDecision&MockInterface $dossier;
    private ProductionReportProcessRun&MockInterface $processRun;
    private ProductionReportStatus $status;

    protected function setUp(): void
    {
        $this->processRun = Mockery::mock(ProductionReportProcessRun::class);
        $this->dossier = Mockery::mock(WooDecision::class);

        $this->status = new ProductionReportStatus($this->dossier);

        parent::setUp();
    }

    public function testNeedsUploadReturnsTrueWhenThereIsNoProductionReport(): void
    {
        $this->dossier->expects('getProductionReport')->andReturnNull();

        self::assertTrue($this->status->needsUpload());
    }

    public function testNeedsUploadReturnsFalseWhenThereIsAProductionReport(): void
    {
        $this->dossier->expects('getProductionReport')->andReturn(Mockery::mock(ProductionReport::class));

        self::assertFalse($this->status->needsUpload());
    }

    public function testIsReadyForDocumentUploadReturnsTrueWhenAllConditionsAreMet(): void
    {
        $this->dossier->expects('getProcessRun')->andReturn($this->processRun);

        $this->processRun->expects('isFinal')->andReturnTrue();

        $this->dossier->expects('getProductionReport')->andReturn(Mockery::mock(ProductionReport::class));

        self::assertTrue($this->status->isReadyForDocumentUpload());
    }

    public function testIsReadyForDocumentUploadReturnsFalseWhenProductionReportIsNotFinal(): void
    {
        $this->dossier->expects('getProcessRun')->andReturn($this->processRun);

        $this->processRun->expects('isFinal')->andReturnFalse();

        self::assertFalse($this->status->isReadyForDocumentUpload());
    }

    public function testNeedsUpdateReturnsTrueWhenAllConditionsAreMet(): void
    {
        $this->dossier->expects('getProcessRun')->andReturn($this->processRun);

        $this->processRun->expects('isNotFinal')->andReturnTrue();
        $this->processRun->expects('needsConfirmation')->andReturnFalse();

        self::assertTrue($this->status->needsUpdate());
    }

    public function testNeedsUpdateReturnsFalseWhenRunIsFinal(): void
    {
        $this->dossier->expects('getProcessRun')->andReturn($this->processRun);

        $this->processRun->expects('isNotFinal')->andReturnFalse();

        self::assertFalse($this->status->needsUpdate());
    }

    public function testNeedsUpdateReturnsFalseWhenRunNeedsConfirmation(): void
    {
        $this->dossier->expects('getProcessRun')->andReturn($this->processRun);

        $this->processRun->expects('isNotFinal')->andReturnTrue();
        $this->processRun->expects('needsConfirmation')->andReturnTrue();

        self::assertFalse($this->status->needsUpdate());
    }

    public function testIsQueuedReturnsTrueWhenRunIsPending(): void
    {
        $this->dossier->expects('getProcessRun')->andReturn($this->processRun);

        $this->processRun->expects('isPending')->andReturnTrue();

        self::assertTrue($this->status->isQueued());
    }

    public function testIsQueuedReturnsTrueWhenRunIsConfirmed(): void
    {
        $this->dossier->expects('getProcessRun')->andReturn($this->processRun);

        $this->processRun->expects('isPending')->andReturnFalse();
        $this->processRun->expects('isConfirmed')->andReturnTrue();

        self::assertTrue($this->status->isQueued());
    }

    public function testIsQueuedReturnsFalse(): void
    {
        $this->dossier->expects('getProcessRun')->andReturn($this->processRun);

        $this->processRun->expects('isPending')->andReturnFalse();
        $this->processRun->expects('isConfirmed')->andReturnFalse();

        self::assertFalse($this->status->isQueued());
    }

    public function testIsRunningReturnsTrueWhenRunIsComparing(): void
    {
        $this->dossier->expects('getProcessRun')->andReturn($this->processRun);

        $this->processRun->expects('isComparing')->andReturnTrue();

        self::assertTrue($this->status->isRunning());
    }

    public function testIsRunningReturnsTrueWhenRunIsUpdating(): void
    {
        $this->dossier->expects('getProcessRun')->andReturn($this->processRun);

        $this->processRun->expects('isComparing')->andReturnFalse();
        $this->processRun->expects('isUpdating')->andReturnTrue();

        self::assertTrue($this->status->isRunning());
    }

    public function testIsRunningReturnsFalse(): void
    {
        $this->dossier->expects('getProcessRun')->andReturn($this->processRun);

        $this->processRun->expects('isComparing')->andReturnFalse();
        $this->processRun->expects('isUpdating')->andReturnFalse();

        self::assertFalse($this->status->isRunning());
    }

    public function testIsComparingReturnsTrue(): void
    {
        $this->dossier->expects('getProcessRun')->andReturn($this->processRun);

        $this->processRun->expects('isComparing')->andReturnTrue();

        self::assertTrue($this->status->isComparing());
    }

    public function testIsComparingReturnsFalse(): void
    {
        $this->dossier->expects('getProcessRun')->andReturn($this->processRun);

        $this->processRun->expects('isComparing')->andReturnFalse();

        self::assertFalse($this->status->isComparing());
    }

    public function testIsUpdatingReturnsTrue(): void
    {
        $this->dossier->expects('getProcessRun')->andReturn($this->processRun);

        $this->processRun->expects('isUpdating')->andReturnTrue();

        self::assertTrue($this->status->isUpdating());
    }

    public function testIsUpdatingReturnsFalse(): void
    {
        $this->dossier->expects('getProcessRun')->andReturn($this->processRun);

        $this->processRun->expects('isUpdating')->andReturnFalse();

        self::assertFalse($this->status->isUpdating());
    }

    public function testNeedsConfirmationReturnsTrue(): void
    {
        $this->dossier->expects('getProcessRun')->andReturn($this->processRun);

        $this->processRun->expects('needsConfirmation')->andReturnTrue();

        self::assertTrue($this->status->needsConfirmation());
    }

    public function testNeedsConfirmationReturnsFalse(): void
    {
        $this->dossier->expects('getProcessRun')->andReturn($this->processRun);

        $this->processRun->expects('needsConfirmation')->andReturnFalse();

        self::assertFalse($this->status->needsConfirmation());
    }

    public function testGetChangeset(): void
    {
        $this->dossier->expects('getProcessRun')->andReturn($this->processRun);

        $changeset = Mockery::mock(InventoryChangeset::class);
        $changeset->expects('getCounts')->andReturn($counts = ['a' => 1, 'b' => 2, 'c' => 3]);

        $this->processRun->expects('getChangeset')->andReturn($changeset);

        self::assertEquals($counts, $this->status->getChangeset());
    }

    public function testGetChangesetReturnsEmptyArrayForMissingChangeset(): void
    {
        $this->dossier->expects('getProcessRun')->andReturn($this->processRun);

        $this->processRun->expects('getChangeset')->andReturnNull();

        self::assertEquals([], $this->status->getChangeset());
    }

    public function testHasErrorsReturnsFalse(): void
    {
        $this->dossier->expects('getProcessRun')->times(2)->andReturn($this->processRun);

        $this->processRun->expects('isRejected')->andReturnFalse();
        $this->processRun->expects('hasErrors')->andReturnFalse();

        self::assertFalse($this->status->hasErrors());
    }

    public function testHasErrorsReturnsFalseWhenRejected(): void
    {
        $this->dossier->expects('getProcessRun')->andReturn($this->processRun);

        $this->processRun->expects('isRejected')->andReturnTrue();

        self::assertFalse($this->status->hasErrors());
    }

    public function testHasErrorsReturnsTrue(): void
    {
        $this->dossier->expects('getProcessRun')->times(2)->andReturn($this->processRun);

        $this->processRun->expects('isRejected')->andReturnFalse();
        $this->processRun->expects('hasErrors')->andReturnTrue();

        self::assertTrue($this->status->hasErrors());
    }

    public function testGetRunDateReturnsNullWhenThereIsNoProcessRun(): void
    {
        $this->dossier->expects('getProcessRun')->andReturnNull();

        self::assertNull($this->status->getRunDate());
    }

    public function testGetRunDateReturnsNullWhenThereIsNoProcessRunEndDate(): void
    {
        $this->dossier->expects('getProcessRun')->andReturn($this->processRun);
        $this->processRun->expects('getEndedAt')->andReturnNull();

        self::assertNull($this->status->getRunDate());
    }

    public function testGetRunDateReturnsProcessRunEndDate(): void
    {
        $endDate = new DateTimeImmutable('2026-01-01 02:12:56');

        $this->dossier->expects('getProcessRun')->andReturn($this->processRun);
        $this->processRun->expects('getEndedAt')->andReturn($endDate);

        self::assertEquals($endDate, $this->status->getRunDate());
    }
}
