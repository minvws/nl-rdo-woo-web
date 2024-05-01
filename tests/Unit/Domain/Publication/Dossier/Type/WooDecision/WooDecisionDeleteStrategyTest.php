<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision;

use App\Domain\Publication\Dossier\DossierDeleteHelper;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionDeleteStrategy;
use App\Entity\DecisionDocument;
use App\Entity\Document;
use App\Entity\Inventory;
use App\Entity\RawInventory;
use App\Service\BatchDownloadService;
use App\Service\DocumentService;
use App\Service\Inquiry\InquiryService;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Webmozart\Assert\InvalidArgumentException;

class WooDecisionDeleteStrategyTest extends MockeryTestCase
{
    private DossierDeleteHelper&MockInterface $deleteHelper;
    private DocumentService&MockInterface $documentService;
    private BatchDownloadService&MockInterface $batchDownloadService;
    private InquiryService&MockInterface $inquiryService;
    private WooDecisionDeleteStrategy $strategy;

    public function setUp(): void
    {
        $this->deleteHelper = \Mockery::mock(DossierDeleteHelper::class);
        $this->documentService = \Mockery::mock(DocumentService::class);
        $this->batchDownloadService = \Mockery::mock(BatchDownloadService::class);
        $this->inquiryService = \Mockery::mock(InquiryService::class);

        $this->strategy = new WooDecisionDeleteStrategy(
            $this->deleteHelper,
            $this->documentService,
            $this->batchDownloadService,
            $this->inquiryService,
        );
    }

    public function testDeleteThrowsExceptionForInvalidType(): void
    {
        $dossier = \Mockery::mock(Covenant::class);

        $this->expectException(InvalidArgumentException::class);

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

        $rawInventory = \Mockery::mock(RawInventory::class);
        $dossier->shouldReceive('getRawInventory')->andReturn($rawInventory);

        $decisionDocument = \Mockery::mock(DecisionDocument::class);
        $dossier->shouldReceive('getDecisionDocument')->andReturn($decisionDocument);

        $this->deleteHelper->expects('deleteAttachments')->with($attachments);
        $this->deleteHelper->expects('deleteFileForEntity')->with($inventory);
        $this->deleteHelper->expects('deleteFileForEntity')->with($rawInventory);
        $this->deleteHelper->expects('deleteFileForEntity')->with($decisionDocument);

        $this->documentService->expects('removeDocumentFromDossier')->with($dossier, $document, false);
        $this->batchDownloadService->expects('removeAllDownloadsForEntity')->with($dossier);
        $this->inquiryService->expects('removeDossierFromInquiries')->with($dossier);

        $this->strategy->delete($dossier);
    }
}
