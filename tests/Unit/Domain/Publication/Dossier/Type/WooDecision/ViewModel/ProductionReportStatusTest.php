<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\ViewModel;

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
        $this->dossier->shouldReceive('getProcessRun')->andReturn($this->processRun);

        $this->status = new ProductionReportStatus($this->dossier);

        parent::setUp();
    }

    public function testNeedsUploadReturnsTrueWhenThereIsNoProductionReport(): void
    {
        $this->dossier->shouldReceive('getProductionReport')->andReturnNull();

        self::assertTrue($this->status->needsUpload());
    }

    public function testNeedsUploadReturnsFalseWhenThereIsAProductionReport(): void
    {
        $this->dossier->shouldReceive('getProductionReport')->andReturn(Mockery::mock(ProductionReport::class));

        self::assertFalse($this->status->needsUpload());
    }

    public function testIsReadyForDocumentUploadReturnsTrueWhenAllConditionsAreMet(): void
    {
        $this->processRun->shouldReceive('isFinal')->andReturnTrue();

        $this->dossier->shouldReceive('getProductionReport')->andReturn(Mockery::mock(ProductionReport::class));

        self::assertTrue($this->status->isReadyForDocumentUpload());
    }

    public function testIsReadyForDocumentUploadReturnsFalseWhenProductionReportIsNotFinal(): void
    {
        $this->processRun->shouldReceive('isFinal')->andReturnFalse();

        $this->dossier->shouldReceive('getProductionReport')->andReturn(Mockery::mock(ProductionReport::class));

        self::assertFalse($this->status->isReadyForDocumentUpload());
    }

    public function testNeedsUpdateReturnsTrueWhenAllConditionsAreMet(): void
    {
        $this->processRun->shouldReceive('isNotFinal')->andReturnTrue();
        $this->processRun->shouldReceive('needsConfirmation')->andReturnFalse();

        self::assertTrue($this->status->needsUpdate());
    }

    public function testNeedsUpdateReturnsFalseWhenRunIsFinal(): void
    {
        $this->processRun->shouldReceive('isNotFinal')->andReturnFalse();
        $this->processRun->shouldReceive('needsConfirmation')->andReturnFalse();

        self::assertFalse($this->status->needsUpdate());
    }

    public function testNeedsUpdateReturnsFalseWhenRunNeedsConfirmation(): void
    {
        $this->processRun->shouldReceive('isNotFinal')->andReturnTrue();
        $this->processRun->shouldReceive('needsConfirmation')->andReturnTrue();

        self::assertFalse($this->status->needsUpdate());
    }

    public function testIsQueuedReturnsTrueWhenRunIsPending(): void
    {
        $this->processRun->shouldReceive('isPending')->andReturnTrue();

        self::assertTrue($this->status->isQueued());
    }

    public function testIsQueuedReturnsTrueWhenRunIsConfirmed(): void
    {
        $this->processRun->shouldReceive('isPending')->andReturnFalse();
        $this->processRun->shouldReceive('isConfirmed')->andReturnTrue();

        self::assertTrue($this->status->isQueued());
    }

    public function testIsQueuedReturnsFalse(): void
    {
        $this->processRun->shouldReceive('isPending')->andReturnFalse();
        $this->processRun->shouldReceive('isConfirmed')->andReturnFalse();

        self::assertFalse($this->status->isQueued());
    }

    public function testIsRunningReturnsTrueWhenRunIsComparing(): void
    {
        $this->processRun->shouldReceive('isComparing')->andReturnTrue();

        self::assertTrue($this->status->isRunning());
    }

    public function testIsRunningReturnsTrueWhenRunIsUpdating(): void
    {
        $this->processRun->shouldReceive('isComparing')->andReturnFalse();
        $this->processRun->shouldReceive('isUpdating')->andReturnTrue();

        self::assertTrue($this->status->isRunning());
    }

    public function testIsRunningReturnsFalse(): void
    {
        $this->processRun->shouldReceive('isComparing')->andReturnFalse();
        $this->processRun->shouldReceive('isUpdating')->andReturnFalse();

        self::assertFalse($this->status->isRunning());
    }

    public function testIsComparingReturnsTrue(): void
    {
        $this->processRun->shouldReceive('isComparing')->andReturnTrue();

        self::assertTrue($this->status->isComparing());
    }

    public function testIsComparingReturnsFalse(): void
    {
        $this->processRun->shouldReceive('isComparing')->andReturnFalse();

        self::assertFalse($this->status->isComparing());
    }

    public function testIsUpdatingReturnsTrue(): void
    {
        $this->processRun->shouldReceive('isUpdating')->andReturnTrue();

        self::assertTrue($this->status->isUpdating());
    }

    public function testIsUpdatingReturnsFalse(): void
    {
        $this->processRun->shouldReceive('isUpdating')->andReturnFalse();

        self::assertFalse($this->status->isUpdating());
    }

    public function testNeedsConfirmationReturnsTrue(): void
    {
        $this->processRun->shouldReceive('needsConfirmation')->andReturnTrue();

        self::assertTrue($this->status->needsConfirmation());
    }

    public function testNeedsConfirmationReturnsFalse(): void
    {
        $this->processRun->shouldReceive('needsConfirmation')->andReturnFalse();

        self::assertFalse($this->status->needsConfirmation());
    }

    public function testGetChangeset(): void
    {
        $changeset = Mockery::mock(InventoryChangeset::class);
        $changeset->shouldReceive('getCounts')->andReturn($counts = ['a' => 1, 'b' => 2, 'c' => 3]);

        $this->processRun->shouldReceive('getChangeset')->andReturn($changeset);

        self::assertEquals($counts, $this->status->getChangeset());
    }

    public function testGetChangesetReturnsEmptyArrayForMissingChangeset(): void
    {
        $this->processRun->shouldReceive('getChangeset')->andReturnNull();

        self::assertEquals([], $this->status->getChangeset());
    }

    public function testHasErrorsReturnsFalse(): void
    {
        $this->processRun->shouldReceive('isRejected')->andReturnFalse();
        $this->processRun->shouldReceive('hasErrors')->andReturnFalse();

        self::assertFalse($this->status->hasErrors());
    }

    public function testHasErrorsReturnsFalseWhenRejected(): void
    {
        $this->processRun->shouldReceive('isRejected')->andReturnTrue();
        $this->processRun->shouldReceive('hasErrors')->andReturnTrue();

        self::assertFalse($this->status->hasErrors());
    }

    public function testHasErrorsReturnsTrue(): void
    {
        $this->processRun->shouldReceive('isRejected')->andReturnFalse();
        $this->processRun->shouldReceive('hasErrors')->andReturnTrue();

        self::assertTrue($this->status->hasErrors());
    }
}
