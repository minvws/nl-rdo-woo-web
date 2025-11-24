<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Decision\DecisionType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inventory\Inventory;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReport;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportProcessRun;
use Shared\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Tests\Unit\UnitTestCase;

final class WooDecisionTest extends UnitTestCase
{
    private WooDecision $wooDecision;

    protected function setUp(): void
    {
        $this->wooDecision = new WooDecision();

        parent::setUp();
    }

    public function testGetGetUploadStatus(): void
    {
        $uploadStatus = $this->wooDecision->getUploadStatus();

        self::assertSame($uploadStatus->getDossier(), $this->wooDecision);
    }

    public function testAddAndRemoveDocument(): void
    {
        $document = \Mockery::mock(Document::class);

        self::assertTrue($this->wooDecision->getDocuments()->isEmpty());

        $document->expects('addDossier')->with($this->wooDecision);
        $this->wooDecision->addDocument($document);
        self::assertEquals([$document], $this->wooDecision->getDocuments()->toArray());

        $document->expects('removeDossier')->with($this->wooDecision);
        $this->wooDecision->removeDocument($document);
        self::assertTrue($this->wooDecision->getDocuments()->isEmpty());
    }

    public function testAddAndRemoveInquiry(): void
    {
        $inquiry = \Mockery::mock(Inquiry::class);

        self::assertTrue($this->wooDecision->getDocuments()->isEmpty());

        $inquiry->expects('addDossier')->with($this->wooDecision);
        $this->wooDecision->addInquiry($inquiry);
        self::assertEquals([$inquiry], $this->wooDecision->getInquiries()->toArray());

        $inquiry->expects('removeDossier')->with($this->wooDecision);
        $this->wooDecision->removeInquiry($inquiry);
        self::assertTrue($this->wooDecision->getInquiries()->isEmpty());
    }

    public function testSetProductionReport(): void
    {
        $otherDossier = new WooDecision();

        $productionReport = \Mockery::mock(ProductionReport::class);
        $productionReport->expects('getDossier')->andReturn($otherDossier);

        $productionReport->expects('setDossier')->with($this->wooDecision);
        $this->wooDecision->setProductionReport($productionReport);
        self::assertSame($productionReport, $this->wooDecision->getProductionReport());
    }

    public function testSetAndGetInventory(): void
    {
        $inventory = \Mockery::mock(Inventory::class);

        $this->wooDecision->setInventory($inventory);
        self::assertSame($inventory, $this->wooDecision->getInventory());
    }

    public function testSetAndGetPublicationReason(): void
    {
        $reason = PublicationReason::WOO_REQUEST;

        $this->wooDecision->setPublicationReason($reason);
        self::assertSame($reason, $this->wooDecision->getPublicationReason());
    }

    #[DataProvider('decisionTypeDataForRequiredInventory')]
    public function testIsInventoryRequiredForDecisionType(DecisionType $decisionType, bool $expectedIsInventoryRequired): void
    {
        $this->wooDecision->setDecision($decisionType);

        self::assertEquals($expectedIsInventoryRequired, $this->wooDecision->isInventoryRequired());
    }

    /**
     * @return array<array{DecisionType,bool}>
     */
    public static function decisionTypeDataForRequiredInventory(): array
    {
        return self::mapDecisionTypesToTrue([DecisionType::PUBLIC, DecisionType::PARTIAL_PUBLIC]);
    }

    #[DataProvider('decisionTypeDataForOptionalInventory')]
    public function testIsInventoryOptionalForDecisionType(DecisionType $decisionType, bool $expectedIsInventoryOptional): void
    {
        $this->wooDecision->setDecision($decisionType);

        self::assertEquals($expectedIsInventoryOptional, $this->wooDecision->isInventoryOptional());
    }

    /**
     * @return array<array{DecisionType,bool}>
     */
    public static function decisionTypeDataForOptionalInventory(): array
    {
        return self::mapDecisionTypesToTrue([DecisionType::ALREADY_PUBLIC, DecisionType::NOT_PUBLIC]);
    }

    #[DataProvider('decisionTypeDataForCanProvideInventory')]
    public function testCanProvideInventoryForDecisionType(DecisionType $decisionType, bool $expectedCanProvideInventory): void
    {
        $this->wooDecision->setDecision($decisionType);

        self::assertEquals($expectedCanProvideInventory, $this->wooDecision->canProvideInventory());
    }

    /**
     * @return array<array{DecisionType,bool}>
     */
    public static function decisionTypeDataForCanProvideInventory(): array
    {
        return self::mapDecisionTypesToTrue(
            [DecisionType::PUBLIC, DecisionType::PARTIAL_PUBLIC, DecisionType::ALREADY_PUBLIC, DecisionType::NOT_PUBLIC],
        );
    }

    /**
     * @param DecisionType[] $decisionTypes
     *
     * @return array<array{DecisionType,bool}>
     */
    public static function mapDecisionTypesToTrue(array $decisionTypes): array
    {
        return array_map(fn (DecisionType $decisionType) => [$decisionType, in_array($decisionType, $decisionTypes, true)], DecisionType::cases());
    }

    public function testSetAndGetPreviewDate(): void
    {
        $previewDate = new \DateTimeImmutable();

        $this->wooDecision->setPreviewDate($previewDate);
        self::assertEquals($previewDate, $this->wooDecision->getPreviewDate());
    }

    public function testHasFuturePreviewDateReturnsTrueForTomorrow(): void
    {
        $previewDate = CarbonImmutable::tomorrow();

        $this->wooDecision->setPreviewDate($previewDate);
        self::assertTrue($this->wooDecision->hasFuturePreviewDate());
    }

    public function testHasFuturePreviewDateReturnsFalseForToday(): void
    {
        $previewDate = CarbonImmutable::today();

        $this->wooDecision->setPreviewDate($previewDate);
        self::assertFalse($this->wooDecision->hasFuturePreviewDate());
    }

    public function testSetAndGetProcessRun(): void
    {
        $processRun = \Mockery::mock(ProductionReportProcessRun::class);

        $this->wooDecision->setProcessRun($processRun);
        self::assertSame($processRun, $this->wooDecision->getProcessRun());
    }

    public function testSetAndGetProcessRunOverwritesFinalRun(): void
    {
        $finalProcessRun = \Mockery::mock(ProductionReportProcessRun::class);
        $finalProcessRun->expects('isNotFinal')->andReturnFalse();

        $this->wooDecision->setProcessRun($finalProcessRun);

        $newProcessRun = \Mockery::mock(ProductionReportProcessRun::class);
        $this->wooDecision->setProcessRun($newProcessRun);

        self::assertSame($newProcessRun, $this->wooDecision->getProcessRun());
    }

    public function testSetAndGetProcessRunThrowsExceptionWhenOverwritingANonFinalRun(): void
    {
        $finalProcessRun = \Mockery::mock(ProductionReportProcessRun::class);
        $finalProcessRun->expects('isNotFinal')->andReturnTrue();

        $this->wooDecision->setProcessRun($finalProcessRun);

        $newProcessRun = \Mockery::mock(ProductionReportProcessRun::class);

        $this->expectException(\RuntimeException::class);

        $this->wooDecision->setProcessRun($newProcessRun);
    }

    public function testSetAndGetDecision(): void
    {
        $decision = DecisionType::NOTHING_FOUND;

        $this->wooDecision->setDecision($decision);
        self::assertEquals($decision, $this->wooDecision->getDecision());
    }

    public function testSetAndGetDecisionDate(): void
    {
        $decisionDate = new \DateTimeImmutable();

        $this->wooDecision->setDecisionDate($decisionDate);
        self::assertEquals($decisionDate, $this->wooDecision->getDecisionDate());
    }

    public function testHasWithdrawnOrSuspendedDocuments(): void
    {
        self::assertFalse($this->wooDecision->hasWithdrawnOrSuspendedDocuments());

        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('addDossier')->with($this->wooDecision);
        $document->shouldReceive('isWithdrawn')->andReturnFalse();
        $document->shouldReceive('isSuspended')->andReturnFalse();
        $this->wooDecision->addDocument($document);

        self::assertFalse($this->wooDecision->hasWithdrawnOrSuspendedDocuments());

        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('addDossier')->with($this->wooDecision);
        $document->shouldReceive('isWithdrawn')->andReturnFalse();
        $document->shouldReceive('isSuspended')->andReturnTrue();
        $this->wooDecision->addDocument($document);

        self::assertTrue($this->wooDecision->hasWithdrawnOrSuspendedDocuments());
    }
}
