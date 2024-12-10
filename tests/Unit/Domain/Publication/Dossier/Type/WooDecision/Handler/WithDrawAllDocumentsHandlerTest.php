<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Handler;

use App\Domain\Publication\Dossier\Type\WooDecision\Command\WithDrawAllDocumentsCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentDispatcher;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\Event\AllDocumentsWithDrawnEvent;
use App\Domain\Publication\Dossier\Type\WooDecision\Handler\WithDrawAllDocumentsHandler;
use App\Domain\Publication\Dossier\Type\WooDecision\WithdrawReason;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class WithDrawAllDocumentsHandlerTest extends MockeryTestCase
{
    private MessageBusInterface&MockInterface $messageBus;
    private DossierWorkflowManager&MockInterface $dossierWorkflowManager;
    private DocumentDispatcher&MockInterface $documentDispatcher;
    private WithDrawAllDocumentsHandler $handler;
    private WooDecision&MockInterface $dossier;

    public function setUp(): void
    {
        $this->dossier = \Mockery::mock(WooDecision::class);
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);
        $this->documentDispatcher = \Mockery::mock(DocumentDispatcher::class);
        $this->dossierWorkflowManager = \Mockery::mock(DossierWorkflowManager::class);

        $this->handler = new WithDrawAllDocumentsHandler(
            $this->dossierWorkflowManager,
            $this->messageBus,
            $this->documentDispatcher,
        );

        parent::setUp();
    }

    public function testWithDrawAllDocumentsSuccessfully(): void
    {
        $reason = WithdrawReason::DATA_IN_DOCUMENT;
        $explanation = 'foo bar';

        $documentWithUpload = \Mockery::mock(Document::class);
        $documentWithUpload->shouldReceive('shouldBeUploaded')->andReturnTrue();
        $documentWithUpload->shouldReceive('isWithdrawn')->andReturnFalse();

        $documentWithoutUpload = \Mockery::mock(Document::class);
        $documentWithoutUpload->shouldReceive('shouldBeUploaded')->andReturnFalse();

        $this->dossier->shouldReceive('getDocuments')->andReturn(new ArrayCollection([
            $documentWithUpload,
            $documentWithoutUpload,
        ]));

        $this->dossierWorkflowManager->expects('applyTransition')->with($this->dossier, DossierStatusTransition::UPDATE_DOCUMENTS);

        $this->documentDispatcher->expects('dispatchWithdrawDocumentCommand')->with(
            $this->dossier,
            $documentWithUpload,
            $reason,
            $explanation,
        );

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            function (AllDocumentsWithDrawnEvent $message) use ($reason, $explanation) {
                return $message->explanation === $explanation
                    && $message->reason === $reason
                    && $message->dossier === $this->dossier;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->handler->__invoke(
            new WithDrawAllDocumentsCommand($this->dossier, $reason, $explanation)
        );
    }
}
