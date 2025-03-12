<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\BatchDownload\EventHandler;

use App\Domain\Publication\BatchDownload\BatchDownloadScope;
use App\Domain\Publication\BatchDownload\BatchDownloadService;
use App\Domain\Publication\BatchDownload\EventHandler\DocumentEventHandler;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawReason;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Event\AllDocumentsWithDrawnEvent;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Event\DocumentWithDrawnEvent;
use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class DocumentEventHandlerTest extends MockeryTestCase
{
    private BatchDownloadService&MockInterface $batchDownloadService;
    private DocumentEventHandler $handler;

    public function setUp(): void
    {
        $this->batchDownloadService = \Mockery::mock(BatchDownloadService::class);

        $this->handler = new DocumentEventHandler(
            $this->batchDownloadService,
        );

        parent::setUp();
    }

    public function testHandleDocumentWithdrawn(): void
    {
        $inquiry = \Mockery::mock(Inquiry::class);

        $dossierA = \Mockery::mock(WooDecision::class);
        $dossierB = \Mockery::mock(WooDecision::class);

        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getDossiers')->andReturn(new ArrayCollection([$dossierA, $dossierB]));
        $document->shouldReceive('getInquiries')->andReturn(new ArrayCollection([$inquiry]));

        $this->batchDownloadService->expects('refresh')->with(\Mockery::on(
            static fn (BatchDownloadScope $scope) => $scope->wooDecision === $dossierA,
        ));
        $this->batchDownloadService->expects('refresh')->with(\Mockery::on(
            static fn (BatchDownloadScope $scope) => $scope->wooDecision === $dossierB,
        ));
        $this->batchDownloadService->expects('refresh')->with(\Mockery::on(
            static fn (BatchDownloadScope $scope) => $scope->inquiry === $inquiry,
        ));
        $this->batchDownloadService->expects('refresh')->with(\Mockery::on(
            static fn (BatchDownloadScope $scope) => $scope->wooDecision === $dossierA && $scope->inquiry === $inquiry,
        ));
        $this->batchDownloadService->expects('refresh')->with(\Mockery::on(
            static fn (BatchDownloadScope $scope) => $scope->wooDecision === $dossierB && $scope->inquiry === $inquiry,
        ));

        $event = new DocumentWithDrawnEvent($document, DocumentWithdrawReason::DATA_IN_DOCUMENT, 'foo', false);

        $this->handler->handleDocumentWithdrawn($event);
    }

    public function testHandleDocumentWithdrawnSkipsBulkWithdraw(): void
    {
        $document = \Mockery::mock(Document::class);

        $event = new DocumentWithDrawnEvent($document, DocumentWithdrawReason::DATA_IN_DOCUMENT, 'foo', true);

        $this->batchDownloadService->shouldNotHaveBeenCalled();

        $this->handler->handleDocumentWithdrawn($event);
    }

    public function testHandleAllDocumentsWithdrawn(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);

        $this->batchDownloadService->expects('refresh')->with(\Mockery::on(
            static fn (BatchDownloadScope $scope) => $scope->wooDecision === $dossier,
        ));

        $event = new AllDocumentsWithDrawnEvent($dossier, DocumentWithdrawReason::DATA_IN_DOCUMENT, 'foo');

        $this->handler->handleAllDocumentsWithdrawn($event);
    }
}
