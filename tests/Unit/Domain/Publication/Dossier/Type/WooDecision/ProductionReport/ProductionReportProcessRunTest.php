<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\ProductionReport;

use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReport;
use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportProcessRun;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\FileInfo;
use App\Exception\ProcessInventoryException;
use App\Service\Inventory\InventoryChangeset;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;

final class ProductionReportProcessRunTest extends UnitTestCase
{
    private WooDecision&MockInterface $wooDecision;
    private ProductionReportProcessRun $productionReportProcessRun;

    public function setUp(): void
    {
        $this->wooDecision = \Mockery::mock(WooDecision::class);
        $this->wooDecision->expects('setProcessRun');

        $this->productionReportProcessRun = new ProductionReportProcessRun($this->wooDecision);

        parent::setUp();
    }

    public function testStartComparing(): void
    {
        $this->productionReportProcessRun->startComparing();
        self::assertEquals($this->wooDecision, $this->productionReportProcessRun->getDossier());

        self::assertEquals(ProductionReportProcessRun::STATUS_COMPARING, $this->productionReportProcessRun->getStatus());
        self::assertEquals(0, $this->productionReportProcessRun->getProgress());
        self::assertFalse($this->productionReportProcessRun->isFinal());

        self::assertFalse($this->productionReportProcessRun->isPending());
        self::assertTrue($this->productionReportProcessRun->isComparing());
        self::assertFalse($this->productionReportProcessRun->needsConfirmation());
        self::assertFalse($this->productionReportProcessRun->isConfirmed());
        self::assertFalse($this->productionReportProcessRun->isRejected());
        self::assertFalse($this->productionReportProcessRun->isUpdating());
        self::assertFalse($this->productionReportProcessRun->isFailed());
        self::assertFalse($this->productionReportProcessRun->isFinished());
        self::assertNotNull($this->productionReportProcessRun->getStartedAt());
    }

    public function testStartComparingCannotBeDoneTwice(): void
    {
        $this->productionReportProcessRun->startComparing();

        $this->expectException(\RuntimeException::class);
        $this->productionReportProcessRun->startComparing();
    }

    public function testSetChangesetSetsStatusToNeedsConfirmationWhenDossierAlreadyHasAProductionReport(): void
    {
        $this->wooDecision->expects('getProductionReport')->andReturn(\Mockery::mock(ProductionReport::class));

        $changeset = \Mockery::mock(InventoryChangeset::class);
        $changeset->expects('getAll')->andReturn($changes = [
            'doc-1' => 'added',
            'doc-2' => 'added',
        ]);

        $this->productionReportProcessRun->setChangeset($changeset);

        self::assertEquals($changes, $this->productionReportProcessRun->getChangeset()?->getAll());
        self::assertEquals(ProductionReportProcessRun::STATUS_NEEDS_CONFIRMATION, $this->productionReportProcessRun->getStatus());
        self::assertFalse($this->productionReportProcessRun->isFinal());
        self::assertTrue($this->productionReportProcessRun->isNotFinal());

        self::assertFalse($this->productionReportProcessRun->isPending());
        self::assertFalse($this->productionReportProcessRun->isComparing());
        self::assertTrue($this->productionReportProcessRun->needsConfirmation());
        self::assertFalse($this->productionReportProcessRun->isConfirmed());
        self::assertFalse($this->productionReportProcessRun->isRejected());
        self::assertFalse($this->productionReportProcessRun->isUpdating());
        self::assertFalse($this->productionReportProcessRun->isFailed());
        self::assertFalse($this->productionReportProcessRun->isFinished());
    }

    public function testSetChangesetSetsStatusToConfirmedWhenDossierHasNoProductionReportYet(): void
    {
        $this->wooDecision->expects('getProductionReport')->andReturnNull();

        $changeset = \Mockery::mock(InventoryChangeset::class);
        $changeset->expects('getAll')->andReturn($changes = [
            'doc-1' => 'added',
            'doc-2' => 'added',
        ]);

        $this->productionReportProcessRun->setChangeset($changeset);

        self::assertEquals($changes, $this->productionReportProcessRun->getChangeset()?->getAll());
        self::assertEquals(ProductionReportProcessRun::STATUS_CONFIRMED, $this->productionReportProcessRun->getStatus());
    }

    public function testConfirm(): void
    {
        $this->wooDecision->expects('getProductionReport')->andReturn(\Mockery::mock(ProductionReport::class));

        $changeset = \Mockery::mock(InventoryChangeset::class);
        $changeset->expects('getAll')->andReturn([]);

        $this->productionReportProcessRun->setChangeset($changeset);
        $this->productionReportProcessRun->confirm();

        self::assertEquals(ProductionReportProcessRun::STATUS_CONFIRMED, $this->productionReportProcessRun->getStatus());
        self::assertEquals(0, $this->productionReportProcessRun->getProgress());
        self::assertFalse($this->productionReportProcessRun->isFinal());
        self::assertTrue($this->productionReportProcessRun->isNotFinal());

        self::assertFalse($this->productionReportProcessRun->isPending());
        self::assertFalse($this->productionReportProcessRun->isComparing());
        self::assertFalse($this->productionReportProcessRun->needsConfirmation());
        self::assertTrue($this->productionReportProcessRun->isConfirmed());
        self::assertFalse($this->productionReportProcessRun->isRejected());
        self::assertFalse($this->productionReportProcessRun->isUpdating());
        self::assertFalse($this->productionReportProcessRun->isFailed());
        self::assertFalse($this->productionReportProcessRun->isFinished());
    }

    public function testConfirmThrowsExceptionForInvalidStatus(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->productionReportProcessRun->confirm();
    }

    public function testReject(): void
    {
        $this->wooDecision->expects('getProductionReport')->andReturn(\Mockery::mock(ProductionReport::class));

        $changeset = \Mockery::mock(InventoryChangeset::class);
        $changeset->expects('getAll')->andReturn([]);

        $this->productionReportProcessRun->setChangeset($changeset);
        $this->productionReportProcessRun->reject();

        self::assertEquals(ProductionReportProcessRun::STATUS_REJECTED, $this->productionReportProcessRun->getStatus());
        self::assertEquals(0, $this->productionReportProcessRun->getProgress());
        self::assertTrue($this->productionReportProcessRun->isFinal());
        self::assertFalse($this->productionReportProcessRun->isNotFinal());

        self::assertFalse($this->productionReportProcessRun->isPending());
        self::assertFalse($this->productionReportProcessRun->isComparing());
        self::assertFalse($this->productionReportProcessRun->needsConfirmation());
        self::assertFalse($this->productionReportProcessRun->isConfirmed());
        self::assertTrue($this->productionReportProcessRun->isRejected());
        self::assertFalse($this->productionReportProcessRun->isUpdating());
        self::assertFalse($this->productionReportProcessRun->isFailed());
        self::assertFalse($this->productionReportProcessRun->isFinished());
    }

    public function testRejectThrowsExceptionForInvalidStatus(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->productionReportProcessRun->reject();
    }

    public function testStartUpdating(): void
    {
        $this->wooDecision->expects('getProductionReport')->andReturn(\Mockery::mock(ProductionReport::class));

        $changeset = \Mockery::mock(InventoryChangeset::class);
        $changeset->expects('getAll')->andReturn([]);

        $this->productionReportProcessRun->setChangeset($changeset);
        $this->productionReportProcessRun->confirm();
        $this->productionReportProcessRun->startUpdating();

        self::assertEquals(ProductionReportProcessRun::STATUS_UPDATING, $this->productionReportProcessRun->getStatus());
        self::assertEquals(0, $this->productionReportProcessRun->getProgress());
        self::assertFalse($this->productionReportProcessRun->isFinal());
        self::assertTrue($this->productionReportProcessRun->isNotFinal());

        self::assertFalse($this->productionReportProcessRun->isPending());
        self::assertFalse($this->productionReportProcessRun->isComparing());
        self::assertFalse($this->productionReportProcessRun->needsConfirmation());
        self::assertFalse($this->productionReportProcessRun->isConfirmed());
        self::assertFalse($this->productionReportProcessRun->isRejected());
        self::assertTrue($this->productionReportProcessRun->isUpdating());
        self::assertFalse($this->productionReportProcessRun->isFailed());
        self::assertFalse($this->productionReportProcessRun->isFinished());
    }

    public function testStartUpdatingIsOnlyPossibleWithValidStatus(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->productionReportProcessRun->startUpdating();
    }

    public function testFinish(): void
    {
        $this->wooDecision->expects('getProductionReport')->andReturn(\Mockery::mock(ProductionReport::class));

        $changeset = \Mockery::mock(InventoryChangeset::class);
        $changeset->expects('getAll')->andReturn([]);

        $this->productionReportProcessRun->setChangeset($changeset);
        $this->productionReportProcessRun->confirm();
        $this->productionReportProcessRun->startUpdating();
        $this->productionReportProcessRun->finish();

        self::assertEquals(ProductionReportProcessRun::STATUS_FINISHED, $this->productionReportProcessRun->getStatus());
        self::assertEquals(100, $this->productionReportProcessRun->getProgress());
        self::assertTrue($this->productionReportProcessRun->isFinal());
        self::assertFalse($this->productionReportProcessRun->isNotFinal());

        self::assertFalse($this->productionReportProcessRun->isPending());
        self::assertFalse($this->productionReportProcessRun->isComparing());
        self::assertFalse($this->productionReportProcessRun->needsConfirmation());
        self::assertFalse($this->productionReportProcessRun->isConfirmed());
        self::assertFalse($this->productionReportProcessRun->isRejected());
        self::assertFalse($this->productionReportProcessRun->isUpdating());
        self::assertFalse($this->productionReportProcessRun->isFailed());
        self::assertTrue($this->productionReportProcessRun->isFinished());
        self::assertNotNull($this->productionReportProcessRun->getEndedAt());
    }

    public function testFail(): void
    {
        $this->wooDecision->expects('getProductionReport')->andReturn(\Mockery::mock(ProductionReport::class));

        $changeset = \Mockery::mock(InventoryChangeset::class);
        $changeset->expects('getAll')->andReturn([]);

        $this->productionReportProcessRun->setChangeset($changeset);
        $this->productionReportProcessRun->confirm();
        $this->productionReportProcessRun->startUpdating();
        $this->productionReportProcessRun->fail();

        self::assertEquals(ProductionReportProcessRun::STATUS_FAILED, $this->productionReportProcessRun->getStatus());
        self::assertEquals(100, $this->productionReportProcessRun->getProgress());
        self::assertTrue($this->productionReportProcessRun->isFinal());
        self::assertFalse($this->productionReportProcessRun->isNotFinal());

        self::assertFalse($this->productionReportProcessRun->isPending());
        self::assertFalse($this->productionReportProcessRun->isComparing());
        self::assertFalse($this->productionReportProcessRun->needsConfirmation());
        self::assertFalse($this->productionReportProcessRun->isConfirmed());
        self::assertFalse($this->productionReportProcessRun->isRejected());
        self::assertFalse($this->productionReportProcessRun->isUpdating());
        self::assertTrue($this->productionReportProcessRun->isFailed());
        self::assertFalse($this->productionReportProcessRun->isFinished());
    }

    public function testSetAndGetTmpFilename(): void
    {
        $this->productionReportProcessRun->setTmpFilename($name = 'foo.bar');
        self::assertEquals($name, $this->productionReportProcessRun->getTmpFilename());
    }

    public function testAddGenericException(): void
    {
        self::assertFalse($this->productionReportProcessRun->hasErrors());

        $this->productionReportProcessRun->addGenericException(
            ProcessInventoryException::forMaxRuntimeExceeded()
        );

        self::assertTrue($this->productionReportProcessRun->hasErrors());
        self::assertCount(1, $this->productionReportProcessRun->getGenericErrors());
        self::assertCount(0, $this->productionReportProcessRun->getRowErrors());
    }

    public function testAddGenericExceptionThrowsExceptionForFinalStatus(): void
    {
        $this->wooDecision->expects('getProductionReport')->andReturn(\Mockery::mock(ProductionReport::class));

        $changeset = \Mockery::mock(InventoryChangeset::class);
        $changeset->expects('getAll')->andReturn([]);

        $this->productionReportProcessRun->setChangeset($changeset);
        $this->productionReportProcessRun->reject();

        $this->expectException(\RuntimeException::class);

        $this->productionReportProcessRun->addGenericException(
            ProcessInventoryException::forMaxRuntimeExceeded()
        );
    }

    public function testAddRowException(): void
    {
        self::assertFalse($this->productionReportProcessRun->hasErrors());

        $this->productionReportProcessRun->addRowException(
            23,
            ProcessInventoryException::forMaxRuntimeExceeded()
        );

        self::assertTrue($this->productionReportProcessRun->hasErrors());
        self::assertCount(0, $this->productionReportProcessRun->getGenericErrors());
        self::assertCount(1, $this->productionReportProcessRun->getRowErrors());
    }

    public function testAddRowExceptionThrowsExceptionForFinalStatus(): void
    {
        $this->wooDecision->expects('getProductionReport')->andReturn(\Mockery::mock(ProductionReport::class));

        $changeset = \Mockery::mock(InventoryChangeset::class);
        $changeset->expects('getAll')->andReturn([]);

        $this->productionReportProcessRun->setChangeset($changeset);
        $this->productionReportProcessRun->reject();

        $this->expectException(\RuntimeException::class);

        $this->productionReportProcessRun->addRowException(
            23,
            ProcessInventoryException::forMaxRuntimeExceeded()
        );
    }

    public function testSetAndGetFileInfo(): void
    {
        $this->productionReportProcessRun->setFileInfo($fileInfo = new FileInfo());

        self::assertEquals($fileInfo, $this->productionReportProcessRun->getFileInfo());
    }

    public function testHasNoErrors(): void
    {
        self::assertTrue($this->productionReportProcessRun->hasNoErrors());

        $this->productionReportProcessRun->addGenericException(
            ProcessInventoryException::forMaxRuntimeExceeded()
        );

        self::assertFalse($this->productionReportProcessRun->hasNoErrors());
    }
}
