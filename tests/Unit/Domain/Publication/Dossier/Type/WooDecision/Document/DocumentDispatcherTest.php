<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Document;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Command\RemoveDocumentCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Command\WithDrawDocumentCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentDispatcher;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawReason;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Event\AllDocumentsWithDrawnEvent;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Event\DocumentRepublishedEvent;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Event\DocumentWithDrawnEvent;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class DocumentDispatcherTest extends UnitTestCase
{
    private MessageBusInterface&MockInterface $messageBus;
    private DocumentDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->messageBus = Mockery::mock(MessageBusInterface::class);

        $this->dispatcher = new DocumentDispatcher(
            $this->messageBus,
        );
    }

    public function testDispatchRemoveDocumentCommand(): void
    {
        $dossierId = Uuid::v6();
        $documentId = Uuid::v6();

        $this->messageBus->expects('dispatch')->with(Mockery::on(
            static function (RemoveDocumentCommand $command) use ($dossierId, $documentId) {
                self::assertEquals($dossierId, $command->getDossierId());
                self::assertEquals($documentId, $command->getDocumentId());

                return true;
            }
        ))->andReturns(new Envelope(new stdClass()));

        $this->dispatcher->dispatchRemoveDocumentCommand(
            $dossierId,
            $documentId,
        );
    }

    public function testDispatchWithdrawDocumentCommand(): void
    {
        $wooDecisionId = Uuid::v6();
        $wooDecision = Mockery::mock(WooDecision::class);
        $wooDecision->shouldReceive('getId')->andReturn($wooDecisionId);

        $documentId = Uuid::v6();
        $document = Mockery::mock(Document::class);
        $document->shouldReceive('getId')->andReturn($documentId);

        $reason = DocumentWithdrawReason::DATA_IN_DOCUMENT;
        $explanation = 'oops';

        $this->messageBus->expects('dispatch')->with(Mockery::on(
            static function (WithDrawDocumentCommand $command) use ($wooDecisionId, $documentId, $reason, $explanation) {
                self::assertEquals($wooDecisionId, $command->dossierId);
                self::assertEquals($documentId, $command->documentId);
                self::assertEquals($reason, $command->reason);
                self::assertEquals($explanation, $command->explanation);

                return true;
            }
        ))->andReturns(new Envelope(new stdClass()));

        $this->dispatcher->dispatchWithdrawDocumentCommand(
            $wooDecision,
            $document,
            $reason,
            $explanation,
        );
    }

    public function testDispatchDocumentWithdrawnEvent(): void
    {
        $document = Mockery::mock(Document::class);
        $reason = DocumentWithdrawReason::DATA_IN_DOCUMENT;
        $explanation = 'oops';

        $this->messageBus->expects('dispatch')->with(Mockery::on(
            static function (DocumentWithDrawnEvent $event) use ($document, $reason, $explanation) {
                self::assertEquals($document, $event->document);
                self::assertEquals($reason, $event->reason);
                self::assertEquals($explanation, $event->explanation);
                self::assertTrue($event->isBulkAction());

                return true;
            }
        ))->andReturns(new Envelope(new stdClass()));

        $this->dispatcher->dispatchDocumentWithdrawnEvent(
            $document,
            $reason,
            $explanation,
            true,
        );
    }

    public function testDispatchAllDocumentsWithdrawnEvent(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $reason = DocumentWithdrawReason::DATA_IN_DOCUMENT;
        $explanation = 'oops';

        $this->messageBus->expects('dispatch')->with(Mockery::on(
            static function (AllDocumentsWithDrawnEvent $event) use ($dossier, $reason, $explanation) {
                self::assertEquals($dossier, $event->dossier);
                self::assertEquals($reason, $event->reason);
                self::assertEquals($explanation, $event->explanation);

                return true;
            }
        ))->andReturns(new Envelope(new stdClass()));

        $this->dispatcher->dispatchAllDocumentsWithdrawnEvent(
            $dossier,
            $reason,
            $explanation,
        );
    }

    public function testDispatchDocumentRepublishedEvent(): void
    {
        $document = Mockery::mock(Document::class);

        $this->messageBus->expects('dispatch')->with(Mockery::on(
            static function (DocumentRepublishedEvent $event) use ($document) {
                self::assertEquals($document, $event->document);

                return true;
            }
        ))->andReturns(new Envelope(new stdClass()));

        $this->dispatcher->dispatchDocumentRepublishedEvent(
            $document,
        );
    }
}
