<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Document\Handler;

use Doctrine\Common\Collections\ArrayCollection;
use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Command\WithDrawAllDocumentsCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawReason;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawService;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Handler\WithDrawAllDocumentsHandler;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\LinkedWooDecisionUpdater;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class WithDrawAllDocumentsHandlerTest extends UnitTestCase
{
    private LinkedWooDecisionUpdater&MockInterface $linkedWooDecisionUpdater;
    private DocumentWithdrawService&MockInterface $documentWithdrawService;
    private WooDecisionRepository&MockInterface $repository;
    private LoggerInterface&MockInterface $logger;
    private WithDrawAllDocumentsHandler $handler;
    private WooDecision&MockInterface $dossier;

    protected function setUp(): void
    {
        $this->dossier = Mockery::mock(WooDecision::class);
        $this->repository = Mockery::mock(WooDecisionRepository::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->documentWithdrawService = Mockery::mock(DocumentWithdrawService::class);
        $this->linkedWooDecisionUpdater = Mockery::mock(LinkedWooDecisionUpdater::class);

        $this->handler = new WithDrawAllDocumentsHandler(
            $this->repository,
            $this->logger,
            $this->linkedWooDecisionUpdater,
            $this->documentWithdrawService,
        );

        parent::setUp();
    }

    public function testWithDrawAllDocumentsSuccessfully(): void
    {
        $dossierId = Uuid::v6();
        $reason = DocumentWithdrawReason::DATA_IN_DOCUMENT;
        $explanation = 'foo bar';
        $documentId = Uuid::v6();
        $document = Mockery::mock(Document::class);
        $document->expects('getId')->andReturn($documentId);

        $this->repository->expects('find')->with($dossierId)->andReturn($this->dossier);
        $this->dossier->expects('getDocuments')->andReturn(new ArrayCollection([$document]));

        $this->linkedWooDecisionUpdater->expects('updateLinkedTo')->with($this->dossier, [$documentId]);

        $this->documentWithdrawService->expects('withDrawAllDocuments')->with($this->dossier, $reason, $explanation);

        $this->handler->__invoke(
            new WithDrawAllDocumentsCommand($dossierId, $reason, $explanation),
        );
    }
}
