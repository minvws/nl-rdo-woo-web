<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Document\Event;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawReason;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Event\AllDocumentsWithDrawnEvent;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Event\DocumentRepublishedEvent;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Event\DocumentWithDrawnEvent;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Handler\DocumentEventHandler;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Service\DossierService;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class DocumentEventHandlerTest extends MockeryTestCase
{
    private DossierService&MockInterface $dossierService;
    private DocumentEventHandler $handler;

    protected function setUp(): void
    {
        $this->dossierService = \Mockery::mock(DossierService::class);

        $this->handler = new DocumentEventHandler(
            $this->dossierService,
        );

        parent::setUp();
    }

    public function testHandleDocumentWithdrawn(): void
    {
        $dossierA = \Mockery::mock(WooDecision::class);
        $dossierB = \Mockery::mock(WooDecision::class);

        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getDossiers')->andReturn(new ArrayCollection([$dossierA, $dossierB]));

        $this->dossierService->expects('validateCompletion')->with($dossierA);
        $this->dossierService->expects('validateCompletion')->with($dossierB);

        $event = new DocumentWithDrawnEvent($document, DocumentWithdrawReason::DATA_IN_DOCUMENT, 'foo', false);

        $this->handler->handleDocumentWithdrawn($event);
    }

    public function testHandleDocumentWithdrawnSkipsBulkWithdraw(): void
    {
        $document = \Mockery::mock(Document::class);

        $event = new DocumentWithDrawnEvent($document, DocumentWithdrawReason::DATA_IN_DOCUMENT, 'foo', true);

        $this->dossierService->shouldNotHaveBeenCalled();

        $this->handler->handleDocumentWithdrawn($event);
    }

    public function testHandleAllDocumentsWithdrawn(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);

        $this->dossierService->expects('validateCompletion')->with($dossier);

        $event = new AllDocumentsWithDrawnEvent($dossier, DocumentWithdrawReason::DATA_IN_DOCUMENT, 'foo');

        $this->handler->handleAllDocumentsWithdrawn($event);
    }

    public function testHandleDocumentRepublished(): void
    {
        $dossierA = \Mockery::mock(WooDecision::class);
        $dossierB = \Mockery::mock(WooDecision::class);

        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getDossiers')->andReturn(new ArrayCollection([$dossierA, $dossierB]));

        $this->dossierService->expects('validateCompletion')->with($dossierA);
        $this->dossierService->expects('validateCompletion')->with($dossierB);

        $event = new DocumentRepublishedEvent($document);

        $this->handler->handleDocumentRepublished($event);
    }
}
