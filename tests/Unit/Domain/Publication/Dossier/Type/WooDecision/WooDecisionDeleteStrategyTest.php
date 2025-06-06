<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision;

use App\Domain\Publication\BatchDownload\BatchDownloadScope;
use App\Domain\Publication\BatchDownload\BatchDownloadService;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Inventory\Inventory;
use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReport;
use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportProcessRun;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionDeleteStrategy;
use App\Service\DocumentService;
use App\Service\Inquiry\InquiryService;
use App\Service\Storage\EntityStorageService;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class WooDecisionDeleteStrategyTest extends MockeryTestCase
{
    private EntityStorageService&MockInterface $entityStorageService;
    private DocumentService&MockInterface $documentService;
    private BatchDownloadService&MockInterface $batchDownloadService;
    private InquiryService&MockInterface $inquiryService;
    private WooDecisionDeleteStrategy $strategy;

    public function setUp(): void
    {
        $this->entityStorageService = \Mockery::mock(EntityStorageService::class);
        $this->documentService = \Mockery::mock(DocumentService::class);
        $this->batchDownloadService = \Mockery::mock(BatchDownloadService::class);
        $this->inquiryService = \Mockery::mock(InquiryService::class);

        $this->strategy = new WooDecisionDeleteStrategy(
            $this->entityStorageService,
            $this->documentService,
            $this->batchDownloadService,
            $this->inquiryService,
        );
    }

    public function testDeleteReturnsEarlyForUnsupportedType(): void
    {
        $dossier = \Mockery::mock(Covenant::class);

        $this->entityStorageService->shouldNotHaveBeenCalled();
        $this->documentService->shouldNotHaveBeenCalled();
        $this->batchDownloadService->shouldNotHaveBeenCalled();
        $this->inquiryService->shouldNotHaveBeenCalled();

        $this->strategy->delete($dossier);
    }

    public function testDelete(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);

        $document = \Mockery::mock(Document::class);
        $dossier->shouldReceive('getDocuments')->andReturn(new ArrayCollection([$document]));

        $attachments = new ArrayCollection([\Mockery::mock(CovenantAttachment::class)]);
        $dossier->shouldReceive('getAttachments')->andReturn($attachments);

        $inventory = \Mockery::mock(Inventory::class);
        $dossier->shouldReceive('getInventory')->andReturn($inventory);

        $productionReport = \Mockery::mock(ProductionReport::class);
        $dossier->shouldReceive('getProductionReport')->andReturn($productionReport);

        $processRun = \Mockery::mock(ProductionReportProcessRun::class);
        $dossier->shouldReceive('getProcessRun')->andReturn($processRun);

        $this->documentService->expects('removeDocumentFromDossier')->with($dossier, $document, false);

        $this->entityStorageService->expects('deleteAllFilesForEntity')->with($inventory);
        $this->entityStorageService->expects('deleteAllFilesForEntity')->with($productionReport);
        $this->entityStorageService->expects('deleteAllFilesForEntity')->with($processRun);

        $this->batchDownloadService->expects('removeAllForScope')->with(\Mockery::on(
            static fn (BatchDownloadScope $scope): bool => $scope->wooDecision === $dossier
        ));
        $this->inquiryService->expects('removeDossierFromInquiries')->with($dossier);

        $this->strategy->delete($dossier);
    }

    public function testDeleteWithoutInventoryAndProcessRun(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);

        $document = \Mockery::mock(Document::class);
        $dossier->shouldReceive('getDocuments')->andReturn(new ArrayCollection([$document]));

        $attachments = new ArrayCollection([\Mockery::mock(CovenantAttachment::class)]);
        $dossier->shouldReceive('getAttachments')->andReturn($attachments);

        $dossier->shouldReceive('getInventory')->andReturnNull();
        $dossier->shouldReceive('getProcessRun')->andReturnNull();

        $productionReport = \Mockery::mock(ProductionReport::class);
        $dossier->shouldReceive('getProductionReport')->andReturn($productionReport);

        $this->documentService->expects('removeDocumentFromDossier')->with($dossier, $document, false);

        $this->entityStorageService->expects('deleteAllFilesForEntity')->with($productionReport);

        $this->batchDownloadService->expects('removeAllForScope')->with(\Mockery::on(
            static fn (BatchDownloadScope $scope): bool => $scope->wooDecision === $dossier
        ));
        $this->inquiryService->expects('removeDossierFromInquiries')->with($dossier);

        $this->strategy->delete($dossier);
    }
}
