<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Document\Handler;

use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Command\WithDrawAllDocumentsCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawReason;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawService;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Handler\WithDrawAllDocumentsHandler;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class WithDrawAllDocumentsHandlerTest extends UnitTestCase
{
    private DossierWorkflowManager&MockInterface $dossierWorkflowManager;
    private DocumentWithdrawService&MockInterface $documentWithdrawService;
    private WooDecisionRepository&MockInterface $repository;
    private LoggerInterface&MockInterface $logger;
    private WithDrawAllDocumentsHandler $handler;
    private WooDecision&MockInterface $dossier;

    protected function setUp(): void
    {
        $this->dossier = \Mockery::mock(WooDecision::class);
        $this->repository = \Mockery::mock(WooDecisionRepository::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->documentWithdrawService = \Mockery::mock(DocumentWithdrawService::class);
        $this->dossierWorkflowManager = \Mockery::mock(DossierWorkflowManager::class);

        $this->handler = new WithDrawAllDocumentsHandler(
            $this->repository,
            $this->logger,
            $this->dossierWorkflowManager,
            $this->documentWithdrawService,
        );

        parent::setUp();
    }

    public function testWithDrawAllDocumentsSuccessfully(): void
    {
        $dossierId = Uuid::v6();
        $reason = DocumentWithdrawReason::DATA_IN_DOCUMENT;
        $explanation = 'foo bar';

        $this->repository->expects('find')->with($dossierId)->andReturn($this->dossier);

        $this->dossierWorkflowManager->expects('applyTransition')->with($this->dossier, DossierStatusTransition::UPDATE_DOCUMENTS);

        $this->documentWithdrawService->expects('withDrawAllDocuments')->with($this->dossier, $reason, $explanation);

        $this->handler->__invoke(
            new WithDrawAllDocumentsCommand($dossierId, $reason, $explanation)
        );
    }
}
