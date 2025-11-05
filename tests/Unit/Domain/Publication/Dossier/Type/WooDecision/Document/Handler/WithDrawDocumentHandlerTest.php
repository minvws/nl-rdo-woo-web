<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Document\Handler;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Command\WithDrawDocumentCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawReason;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawService;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Handler\WithDrawDocumentHandler;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

class WithDrawDocumentHandlerTest extends MockeryTestCase
{
    private WooDecisionRepository&MockInterface $wooDecisionRepository;
    private DocumentRepository&MockInterface $documentRepository;
    private LoggerInterface&MockInterface $logger;
    private DossierWorkflowManager&MockInterface $dossierWorkflowManager;
    private DocumentWithdrawService&MockInterface $documentWithdrawService;
    private WithDrawDocumentHandler $handler;

    protected function setUp(): void
    {
        $this->wooDecisionRepository = \Mockery::mock(WooDecisionRepository::class);
        $this->documentRepository = \Mockery::mock(DocumentRepository::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->dossierWorkflowManager = \Mockery::mock(DossierWorkflowManager::class);
        $this->documentWithdrawService = \Mockery::mock(DocumentWithdrawService::class);

        $this->handler = new WithDrawDocumentHandler(
            $this->wooDecisionRepository,
            $this->documentRepository,
            $this->logger,
            $this->dossierWorkflowManager,
            $this->documentWithdrawService,
        );

        parent::setUp();
    }

    public function testWithDrawSuccessfully(): void
    {
        $dossierId = Uuid::v6();
        $dossier = \Mockery::mock(WooDecision::class);
        $reason = DocumentWithdrawReason::DATA_IN_DOCUMENT;
        $explanation = 'foo bar';

        $documentId = Uuid::v6();
        $document = \Mockery::mock(Document::class);
        $document->shouldReceive('getDossiers')->andReturn(new ArrayCollection([$dossier]));

        $this->wooDecisionRepository->expects('find')->with($dossierId)->andReturn($dossier);
        $this->documentRepository->expects('findOneByDossierAndId')->with($dossier, $documentId)->andReturn($document);

        $this->dossierWorkflowManager->expects('applyTransition')->with($dossier, DossierStatusTransition::UPDATE_DOCUMENTS);

        $this->documentWithdrawService->expects('withdraw')->with($document, $reason, $explanation);

        $this->handler->__invoke(
            new WithDrawDocumentCommand($dossierId, $documentId, $reason, $explanation)
        );
    }
}
