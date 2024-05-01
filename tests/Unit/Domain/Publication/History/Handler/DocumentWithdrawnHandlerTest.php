<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\History\Handler;

use App\Domain\Publication\Dossier\Type\WooDecision\Event\DocumentWithDrawnEvent;
use App\Domain\Publication\History\Handler\Document\DocumentWithDrawnHandler;
use App\Entity\Document;
use App\Entity\WithdrawReason;
use App\Service\HistoryService;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class DocumentWithdrawnHandlerTest extends MockeryTestCase
{
    private HistoryService&MockInterface $historyService;
    private DocumentWithDrawnHandler $handler;

    public function setUp(): void
    {
        $this->historyService = \Mockery::mock(HistoryService::class);

        $this->handler = new DocumentWithDrawnHandler(
            $this->historyService,
        );

        parent::setUp();
    }

    public function testInvoke(): void
    {
        $document = \Mockery::mock(Document::class);
        $reason = WithdrawReason::DATA_IN_DOCUMENT;
        $explanation = 'foo bar';

        $event = new DocumentWithDrawnEvent($document, $reason, $explanation);

        $this->historyService->expects('addDocumentEntry')->with(
            $document,
            'document_withdraw',
            [
                'explanation' => '%data_in_document%',
                'explanation_details' => $explanation,
            ]
        );

        $this->handler->__invoke($event);
    }
}
