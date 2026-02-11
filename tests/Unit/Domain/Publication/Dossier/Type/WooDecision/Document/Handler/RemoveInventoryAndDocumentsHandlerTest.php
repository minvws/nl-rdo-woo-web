<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Document\Handler;

use Doctrine\Common\Collections\ArrayCollection;
use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shared\Domain\Publication\BatchDownload\BatchDownloadScope;
use Shared\Domain\Publication\BatchDownload\BatchDownloadService;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inventory\Command\RemoveInventoryAndDocumentsCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inventory\Handler\RemoveInventoryAndDocumentsHandler;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Service\DocumentService;
use Shared\Service\DossierService;
use Shared\Service\Inventory\InventoryService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class RemoveInventoryAndDocumentsHandlerTest extends UnitTestCase
{
    private WooDecisionRepository&MockInterface $wooDecisionRepository;
    private LoggerInterface&MockInterface $logger;
    private InventoryService&MockInterface $inventoryService;
    private BatchDownloadService&MockInterface $batchDownloadService;
    private DocumentService&MockInterface $documentService;
    private DossierService&MockInterface $dossierService;
    private RemoveInventoryAndDocumentsHandler $handler;

    protected function setUp(): void
    {
        $this->wooDecisionRepository = Mockery::mock(WooDecisionRepository::class);
        $this->inventoryService = Mockery::mock(InventoryService::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->batchDownloadService = Mockery::mock(BatchDownloadService::class);
        $this->documentService = Mockery::mock(DocumentService::class);
        $this->dossierService = Mockery::mock(DossierService::class);

        $this->handler = new RemoveInventoryAndDocumentsHandler(
            $this->wooDecisionRepository,
            $this->inventoryService,
            $this->logger,
            $this->batchDownloadService,
            $this->documentService,
            $this->dossierService,
        );
    }

    public function testInvokeLogsWarningWhenDossierIsNotFound(): void
    {
        $message = new RemoveInventoryAndDocumentsCommand(
            $dossierId = Uuid::v6(),
        );

        $this->wooDecisionRepository->expects('find')->with($dossierId)->andReturn(null);

        $this->logger->expects('warning');

        $this->handler->__invoke($message);
    }

    public function testInvokeLogsErrorForException(): void
    {
        $message = new RemoveInventoryAndDocumentsCommand(
            $dossierId = Uuid::v6(),
        );

        $this->wooDecisionRepository->expects('find')->with($dossierId)->andThrows(new RuntimeException('oops'));

        $this->logger->expects('error');

        $this->handler->__invoke($message);
    }

    public function testInvokeSuccessful(): void
    {
        $message = new RemoveInventoryAndDocumentsCommand(
            $dossierId = Uuid::v6(),
        );

        $document = Mockery::mock(Document::class);
        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getDocuments')->andReturn(new ArrayCollection([$document]));

        $this->wooDecisionRepository->expects('find')->with($dossierId)->andReturn($dossier);

        $this->inventoryService->expects('removeInventories')->with($dossier)->andReturnTrue();
        $this->documentService->expects('removeDocumentFromDossier')->with($dossier, $document);
        $this->batchDownloadService->expects('refresh')->with(Mockery::on(
            static fn (BatchDownloadScope $scope): bool => $scope->wooDecision === $dossier
        ));
        $this->dossierService->expects('validateCompletion')->with($dossier);

        $this->handler->__invoke($message);
    }
}
