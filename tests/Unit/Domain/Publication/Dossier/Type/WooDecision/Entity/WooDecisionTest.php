<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Entity;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\DecisionType;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Inquiry;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Inventory;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\ProductionReport;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\ProductionReportProcessRun;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use App\Tests\Unit\UnitTestCase;
use Carbon\CarbonImmutable;

final class WooDecisionTest extends UnitTestCase
{
    public function testGetBatchFileName(): void
    {
        $dossier = new WooDecision();
        $dossier->setDocumentPrefix('foo');
        $dossier->setDossierNr('bar-123');

        self::assertEquals(
            'foo-bar-123',
            $dossier->getBatchFileName(),
        );
    }

    public function testGetGetUploadStatus(): void
    {
        $dossier = new WooDecision();
        $uploadStatus = $dossier->getUploadStatus();

        self::assertSame($uploadStatus->getDossier(), $dossier);
    }

    public function testAddAndRemoveDocument(): void
    {
        $dossier = new WooDecision();
        $document = \Mockery::mock(Document::class);

        self::assertTrue($dossier->getDocuments()->isEmpty());

        $document->expects('addDossier')->with($dossier);
        $dossier->addDocument($document);
        self::assertEquals([$document], $dossier->getDocuments()->toArray());

        $document->expects('removeDossier')->with($dossier);
        $dossier->removeDocument($document);
        self::assertTrue($dossier->getDocuments()->isEmpty());
    }

    public function testAddAndRemoveInquiry(): void
    {
        $dossier = new WooDecision();
        $inquiry = \Mockery::mock(Inquiry::class);

        self::assertTrue($dossier->getDocuments()->isEmpty());

        $inquiry->expects('addDossier')->with($dossier);
        $dossier->addInquiry($inquiry);
        self::assertEquals([$inquiry], $dossier->getInquiries()->toArray());

        $inquiry->expects('removeDossier')->with($dossier);
        $dossier->removeInquiry($inquiry);
        self::assertTrue($dossier->getInquiries()->isEmpty());
    }

    public function testSetProductionReport(): void
    {
        $dossier = new WooDecision();
        $otherDossier = new WooDecision();

        $productionReport = \Mockery::mock(ProductionReport::class);
        $productionReport->expects('getDossier')->andReturn($otherDossier);

        $productionReport->expects('setDossier')->with($dossier);
        $dossier->setProductionReport($productionReport);
        self::assertSame($productionReport, $dossier->getProductionReport());
    }

    public function testIsAvailableForBatchDownloadReturnsFalseWhenDossierIsNotPubliclyAvailable(): void
    {
        $dossier = new WooDecision();
        $dossier->setStatus(DossierStatus::CONCEPT);

        self::assertFalse($dossier->isAvailableForBatchDownload());
    }

    public function testIsAvailableForBatchDownloadReturnsFalseWhenThereAreNoUploads(): void
    {
        $dossier = new WooDecision();
        $dossier->setStatus(DossierStatus::PUBLISHED);

        $document = \Mockery::mock(Document::class);
        $document->expects('addDossier')->with($dossier);
        $document->expects('shouldBeUploaded')->andReturnTrue();
        $document->expects('isUploaded')->andReturnFalse();

        $dossier->addDocument($document);

        self::assertFalse($dossier->isAvailableForBatchDownload());
    }

    public function testIsAvailableForBatchDownloadTrueWhenThereIsOneUploadedDocument(): void
    {
        $dossier = new WooDecision();
        $dossier->setStatus(DossierStatus::PUBLISHED);

        $document = \Mockery::mock(Document::class);
        $document->expects('addDossier')->with($dossier);
        $document->expects('shouldBeUploaded')->andReturnTrue();
        $document->expects('isUploaded')->andReturnTrue();

        $dossier->addDocument($document);

        self::assertTrue($dossier->isAvailableForBatchDownload());
    }

    public function testSetAndGetInventory(): void
    {
        $wooDecision = new WooDecision();
        $inventory = \Mockery::mock(Inventory::class);

        $wooDecision->setInventory($inventory);
        self::assertSame($inventory, $wooDecision->getInventory());
    }

    public function testSetAndGetPublicationReason(): void
    {
        $wooDecision = new WooDecision();
        $reason = PublicationReason::WOO_REQUEST;

        $wooDecision->setPublicationReason($reason);
        self::assertSame($reason, $wooDecision->getPublicationReason());
    }

    public function testNeedsInventoryAndDocumentsReturnsFalseWhenNothingFound(): void
    {
        $wooDecision = new WooDecision();
        $wooDecision->setDecision(DecisionType::NOTHING_FOUND);

        self::assertFalse($wooDecision->needsInventoryAndDocuments());
    }

    public function testNeedsInventoryAndDocumentsReturnsFalseWhenNotPublic(): void
    {
        $wooDecision = new WooDecision();
        $wooDecision->setDecision(DecisionType::NOT_PUBLIC);

        self::assertFalse($wooDecision->needsInventoryAndDocuments());
    }

    public function testNeedsInventoryAndDocumentsReturnsTrueWhenPublic(): void
    {
        $wooDecision = new WooDecision();
        $wooDecision->setDecision(DecisionType::PUBLIC);

        self::assertTrue($wooDecision->needsInventoryAndDocuments());
    }

    public function testSetAndGetPreviewDate(): void
    {
        $wooDecision = new WooDecision();
        $previewDate = new \DateTimeImmutable();

        $wooDecision->setPreviewDate($previewDate);
        self::assertEquals($previewDate, $wooDecision->getPreviewDate());
    }

    public function testHasFuturePreviewDateReturnsTrueForTomorrow(): void
    {
        $wooDecision = new WooDecision();
        $previewDate = CarbonImmutable::tomorrow();

        $wooDecision->setPreviewDate($previewDate);
        self::assertTrue($wooDecision->hasFuturePreviewDate());
    }

    public function testHasFuturePreviewDateReturnsFalseForToday(): void
    {
        $wooDecision = new WooDecision();
        $previewDate = CarbonImmutable::today();

        $wooDecision->setPreviewDate($previewDate);
        self::assertFalse($wooDecision->hasFuturePreviewDate());
    }

    public function testSetAndGetProcessRun(): void
    {
        $wooDecision = new WooDecision();
        $processRun = \Mockery::mock(ProductionReportProcessRun::class);

        $wooDecision->setProcessRun($processRun);
        self::assertSame($processRun, $wooDecision->getProcessRun());
    }

    public function testSetAndGetProcessRunOverwritesFinalRun(): void
    {
        $finalProcessRun = \Mockery::mock(ProductionReportProcessRun::class);
        $finalProcessRun->expects('isNotFinal')->andReturnFalse();

        $wooDecision = new WooDecision();
        $wooDecision->setProcessRun($finalProcessRun);

        $newProcessRun = \Mockery::mock(ProductionReportProcessRun::class);
        $wooDecision->setProcessRun($newProcessRun);

        self::assertSame($newProcessRun, $wooDecision->getProcessRun());
    }

    public function testSetAndGetProcessRunThrowsExceptionWhenOverwritingANonFinalRun(): void
    {
        $finalProcessRun = \Mockery::mock(ProductionReportProcessRun::class);
        $finalProcessRun->expects('isNotFinal')->andReturnTrue();

        $wooDecision = new WooDecision();
        $wooDecision->setProcessRun($finalProcessRun);

        $newProcessRun = \Mockery::mock(ProductionReportProcessRun::class);

        $this->expectException(\RuntimeException::class);

        $wooDecision->setProcessRun($newProcessRun);
    }

    public function testSetAndGetDecision(): void
    {
        $wooDecision = new WooDecision();
        $decision = DecisionType::NOTHING_FOUND;

        $wooDecision->setDecision($decision);
        self::assertEquals($decision, $wooDecision->getDecision());
    }

    public function testSetAndGetDecisionDate(): void
    {
        $wooDecision = new WooDecision();
        $decisionDate = new \DateTimeImmutable();

        $wooDecision->setDecisionDate($decisionDate);
        self::assertEquals($decisionDate, $wooDecision->getDecisionDate());
    }
}
