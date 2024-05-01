<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\History\Handler;

use App\Domain\Publication\Dossier\Type\WooDecision\Event\AllDocumentsWithDrawnEvent;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\History\Handler\Document\AllDocumentsWithDrawnHandler;
use App\Entity\WithdrawReason;
use App\Service\HistoryService;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;

class AllDocumentsWithdrawnHandlerTest extends UnitTestCase
{
    private HistoryService&MockInterface $historyService;
    private AllDocumentsWithDrawnHandler $handler;

    public function setUp(): void
    {
        $this->historyService = \Mockery::mock(HistoryService::class);

        $this->handler = new AllDocumentsWithDrawnHandler(
            $this->historyService,
        );

        parent::setUp();
    }

    public function testInvoke(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $reason = WithdrawReason::DATA_IN_DOCUMENT;
        $explanation = 'foo bar';

        $event = new AllDocumentsWithDrawnEvent($dossier, $reason, $explanation);

        $this->historyService->expects('addDossierEntry')->with(
            $dossier,
            'dossier_withdraw_all',
            [
                'reason' => '%data_in_document%',
                'explanation' => $explanation,
            ]
        );

        $this->handler->__invoke($event);
    }
}
