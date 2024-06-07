<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\History\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\Event\AllDocumentsWithDrawnEvent;
use App\Domain\Publication\Dossier\Type\WooDecision\Event\DocumentWithDrawnEvent;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\History\WooDecision\DocumentHistoryHandler;
use App\Entity\Document;
use App\Entity\WithdrawReason;
use App\Service\HistoryService;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;

class DocumentHistoryHandlerTest extends UnitTestCase
{
    private HistoryService&MockInterface $historyService;
    private DocumentHistoryHandler $handler;

    public function setUp(): void
    {
        $this->historyService = \Mockery::mock(HistoryService::class);

        $this->handler = new DocumentHistoryHandler(
            $this->historyService,
        );

        parent::setUp();
    }

    public function testHandleAllDocumentsWithdrawn(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $reason = WithdrawReason::DATA_IN_DOCUMENT;
        $explanation = 'foo bar';

        $event = new AllDocumentsWithDrawnEvent($dossier, $reason, $explanation);

        $this->historyService->expects('addDossierEntry')->with(
            $dossier,
            'dossier_withdraw_all',
            [
                'explanation' => '%global.document.withdraw.reason.data_in_document%',
                'explanation_details' => $explanation,
            ]
        );

        $this->handler->handleAllDocumentsWithdrawn($event);
    }

    public function testHandleDocumentWithdrawn(): void
    {
        $document = \Mockery::mock(Document::class);
        $reason = WithdrawReason::DATA_IN_DOCUMENT;
        $explanation = 'foo bar';

        $event = new DocumentWithDrawnEvent($document, $reason, $explanation);

        $this->historyService->expects('addDocumentEntry')->with(
            $document,
            'document_withdraw',
            [
                'explanation' => '%global.document.withdraw.reason.data_in_document%',
                'explanation_details' => $explanation,
            ]
        );

        $this->handler->handleDocumentWithdrawn($event);
    }
}
