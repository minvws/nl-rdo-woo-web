<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\History\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\Event\AllDocumentsWithDrawnEvent;
use App\Domain\Publication\Dossier\Type\WooDecision\Event\DocumentUpdateEvent;
use App\Domain\Publication\Dossier\Type\WooDecision\Event\DocumentWithDrawnEvent;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\History\WooDecision\DocumentHistoryHandler;
use App\Entity\Document;
use App\Entity\Judgement;
use App\Entity\WithdrawReason;
use App\Service\HistoryService;
use App\Service\Inventory\DocumentComparator;
use App\Service\Inventory\DocumentMetadata;
use App\Service\Inventory\MetadataField;
use App\Service\Inventory\PropertyChangeset;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;

class DocumentHistoryHandlerTest extends UnitTestCase
{
    private HistoryService&MockInterface $historyService;
    private DocumentHistoryHandler $handler;
    private DocumentComparator&MockInterface $documentComparator;

    public function setUp(): void
    {
        $this->historyService = \Mockery::mock(HistoryService::class);
        $this->documentComparator = \Mockery::mock(DocumentComparator::class);

        $this->handler = new DocumentHistoryHandler(
            $this->historyService,
            $this->documentComparator,
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

    public function testHandleDocumentUpdated(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getJudgement')->andReturn(Judgement::NOT_PUBLIC);
        $metadata = \Mockery::mock(DocumentMetadata::class);
        $metadata->shouldReceive('getJudgement')->andReturn(Judgement::ALREADY_PUBLIC);
        $metadata->shouldReceive('isSuspended')->andReturnTrue();

        $changeset = \Mockery::mock(PropertyChangeset::class);

        $event = new DocumentUpdateEvent($dossier, $metadata, $document);

        $this->documentComparator->expects('getChangeset')->with($dossier, $document, $metadata)->andReturn($changeset);

        $changeset->expects('isChanged')->with(MetadataField::JUDGEMENT->value)->andReturnTrue();
        $this->historyService->expects('addDocumentEntry')->with(
            $document,
            'document_judgement_already_public',
            [
                'old' => '%not_public%',
                'new' => '%already_public%',
            ],
            HistoryService::MODE_BOTH,
            false,
        );

        $changeset->expects('isChanged')->with(MetadataField::SUSPENDED->value)->andReturnTrue();
        $this->historyService->expects('addDocumentEntry')->with(
            $document,
            'document_suspended',
            [],
            HistoryService::MODE_BOTH,
            false,
        );

        $this->handler->handleDocumentUpdate($event);
    }
}
