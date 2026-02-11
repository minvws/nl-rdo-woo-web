<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\BatchDownload\EventHandler;

use Doctrine\Common\Collections\ArrayCollection;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\BatchDownload\BatchDownloadScope;
use Shared\Domain\Publication\BatchDownload\BatchDownloadService;
use Shared\Domain\Publication\BatchDownload\EventHandler\DocumentEventHandler;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawReason;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Event\AllDocumentsWithDrawnEvent;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Event\DocumentWithDrawnEvent;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Tests\Unit\UnitTestCase;

class DocumentEventHandlerTest extends UnitTestCase
{
    private BatchDownloadService&MockInterface $batchDownloadService;
    private DocumentEventHandler $handler;

    protected function setUp(): void
    {
        $this->batchDownloadService = Mockery::mock(BatchDownloadService::class);

        $this->handler = new DocumentEventHandler(
            $this->batchDownloadService,
        );

        parent::setUp();
    }

    public function testHandleDocumentWithdrawn(): void
    {
        $dossierA = Mockery::mock(WooDecision::class);
        $dossierB = Mockery::mock(WooDecision::class);

        $document = Mockery::mock(Document::class);
        $document->shouldReceive('getDossiers')->andReturn(new ArrayCollection([$dossierA, $dossierB]));

        $this->batchDownloadService->expects('refresh')->with(Mockery::on(
            static fn (BatchDownloadScope $scope) => $scope->wooDecision === $dossierA,
        ));
        $this->batchDownloadService->expects('refresh')->with(Mockery::on(
            static fn (BatchDownloadScope $scope) => $scope->wooDecision === $dossierB,
        ));

        $event = new DocumentWithDrawnEvent($document, DocumentWithdrawReason::DATA_IN_DOCUMENT, 'foo', false);

        $this->handler->handleDocumentWithdrawn($event);
    }

    public function testHandleDocumentWithdrawnSkipsBulkWithdraw(): void
    {
        $document = Mockery::mock(Document::class);

        $event = new DocumentWithDrawnEvent($document, DocumentWithdrawReason::DATA_IN_DOCUMENT, 'foo', true);

        $this->batchDownloadService->shouldNotHaveBeenCalled();

        $this->handler->handleDocumentWithdrawn($event);
    }

    public function testHandleAllDocumentsWithdrawn(): void
    {
        $dossier = Mockery::mock(WooDecision::class);

        $this->batchDownloadService->expects('refresh')->with(Mockery::on(
            static fn (BatchDownloadScope $scope) => $scope->wooDecision === $dossier,
        ));

        $event = new AllDocumentsWithDrawnEvent($dossier, DocumentWithdrawReason::DATA_IN_DOCUMENT, 'foo');

        $this->handler->handleAllDocumentsWithdrawn($event);
    }
}
