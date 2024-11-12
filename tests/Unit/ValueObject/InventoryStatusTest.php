<?php

declare(strict_types=1);

namespace App\Tests\Unit\ValueObject;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Entity\ProductionReport;
use App\Entity\ProductionReportProcessRun;
use App\Service\Inventory\InventoryChangeset;
use App\ValueObject\InventoryStatus;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class InventoryStatusTest extends MockeryTestCase
{
    private WooDecision&MockInterface $dossier;
    private ProductionReportProcessRun&MockInterface $processRun;
    private InventoryStatus $status;

    public function setUp(): void
    {
        $this->processRun = \Mockery::mock(ProductionReportProcessRun::class);
        $this->dossier = \Mockery::mock(WooDecision::class);
        $this->dossier->shouldReceive('getProcessRun')->andReturn($this->processRun);

        $this->status = new InventoryStatus($this->dossier);

        parent::setUp();
    }

    public function testIsUploadedReturnsTrueWhenAllConditionsAreMet(): void
    {
        $this->processRun->shouldReceive('isFinished')->andReturnTrue();
        $this->dossier->shouldReceive('getProductionReport')->andReturn(\Mockery::mock(ProductionReport::class));

        self::assertTrue($this->status->isUploaded());
    }

    public function testIsUploadedReturnsFalseWhenRunIsNotFinished(): void
    {
        $this->processRun->shouldReceive('isFinished')->andReturnFalse();
        $this->dossier->shouldReceive('getProductionReport')->andReturn(\Mockery::mock(ProductionReport::class));

        self::assertFalse($this->status->isUploaded());
    }

    public function testIsUploadedReturnsFalseWhenProductionReportIsMissing(): void
    {
        $this->processRun->shouldReceive('isFinished')->andReturnTrue();
        $this->dossier->shouldReceive('getProductionReport')->andReturnNull();

        self::assertFalse($this->status->isUploaded());
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
        $changeset = \Mockery::mock(InventoryChangeset::class);
        $changeset->shouldReceive('getCounts')->andReturn($counts = ['a' => 1, 'b' => 2, 'c' => 3]);

        $this->processRun->shouldReceive('getChangeset')->andReturn($changeset);

        self::assertEquals($counts, $this->status->getChangeset());
    }

    public function testGetChangesetReturnsEmptyArrayForMissingChangeset(): void
    {
        $this->processRun->shouldReceive('getChangeset')->andReturnNull();

        self::assertEquals([], $this->status->getChangeset());
    }

    public function testCanUploadReturnsTrueForNonConceptDossier(): void
    {
        $this->dossier->shouldReceive('getStatus')->andReturn(DossierStatus::PUBLISHED);

        self::assertTrue($this->status->canUpload());
    }

    public function testCanUploadReturnsTrueForConceptDossierWithFailedRun(): void
    {
        $this->dossier->shouldReceive('getStatus')->andReturn(DossierStatus::CONCEPT);
        $this->processRun->shouldReceive('isFailed')->andReturnTrue();

        self::assertTrue($this->status->canUpload());
    }

    public function testCanUploadReturnsTrueForConceptDossierWithoutProductionReport(): void
    {
        $this->dossier->shouldReceive('getStatus')->andReturn(DossierStatus::CONCEPT);
        $this->processRun->shouldReceive('isFailed')->andReturnFalse();
        $this->dossier->shouldReceive('getProductionReport')->andReturnNull();

        self::assertTrue($this->status->canUpload());
    }

    public function testCanUploadReturnsFalseForConceptDossier(): void
    {
        $this->dossier->shouldReceive('getStatus')->andReturn(DossierStatus::CONCEPT);
        $this->processRun->shouldReceive('isFailed')->andReturnFalse();
        $this->dossier->shouldReceive('getProductionReport')->andReturn(\Mockery::mock(ProductionReport::class));

        self::assertFalse($this->status->canUpload());
    }

    public function testHasErrorsReturnsFalse(): void
    {
        $this->processRun->shouldReceive('hasErrors')->andReturnFalse();

        self::assertFalse($this->status->hasErrors());
    }

    public function testHasErrorsReturnsTrue(): void
    {
        $this->processRun->shouldReceive('hasErrors')->andReturnTrue();

        self::assertTrue($this->status->hasErrors());
    }
}
