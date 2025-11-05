<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Document\Handler;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Command\RemoveDocumentCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Handler\RemoveDocumentHandler;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use App\Service\DocumentService;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

class RemoveDocumentHandlerTest extends UnitTestCase
{
    private WooDecisionRepository&MockInterface $wooDecisionRepository;
    private DocumentRepository&MockInterface $documentRepository;
    private LoggerInterface&MockInterface $logger;
    private DocumentService&MockInterface $documentService;
    private RemoveDocumentHandler $handler;

    protected function setUp(): void
    {
        $this->wooDecisionRepository = \Mockery::mock(WooDecisionRepository::class);
        $this->documentRepository = \Mockery::mock(DocumentRepository::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->documentService = \Mockery::mock(DocumentService::class);

        $this->handler = new RemoveDocumentHandler(
            $this->logger,
            $this->wooDecisionRepository,
            $this->documentRepository,
            $this->documentService,
        );
    }

    public function testInvokeLogsWarningWhenDossierIsNotFound(): void
    {
        $message = new RemoveDocumentCommand(
            $dossierUuid = Uuid::v6(),
            Uuid::v6(),
        );

        $this->wooDecisionRepository->expects('find')->with($dossierUuid)->andReturn(null);

        $this->logger->expects('warning');

        $this->handler->__invoke($message);
    }

    public function testInvokeLogsWarningWhenDocumentIsNotFound(): void
    {
        $message = new RemoveDocumentCommand(
            $dossierUuid = Uuid::v6(),
            $documentUuid = Uuid::v6(),
        );

        $dossier = \Mockery::mock(WooDecision::class);

        $this->wooDecisionRepository->expects('find')->with($dossierUuid)->andReturn($dossier);
        $this->documentRepository->expects('find')->with($documentUuid)->andReturnNull();

        $this->logger->expects('warning');

        $this->handler->__invoke($message);
    }

    public function testInvokeSuccessful(): void
    {
        $message = new RemoveDocumentCommand(
            $dossierUuid = Uuid::v6(),
            $documentUuid = Uuid::v6(),
        );

        $dossier = \Mockery::mock(WooDecision::class);
        $document = \Mockery::mock(Document::class);

        $this->wooDecisionRepository->expects('find')->with($dossierUuid)->andReturn($dossier);
        $this->documentRepository->expects('find')->with($documentUuid)->andReturn($document);

        $this->documentService->expects('removeDocumentFromDossier')->with($dossier, $document);

        $this->handler->__invoke($message);
    }
}
