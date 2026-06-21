<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Inventory\Handler;

use Doctrine\Common\Collections\ArrayCollection;
use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shared\Domain\Publication\BatchDownload\BatchDownloadScope;
use Shared\Domain\Publication\BatchDownload\BatchDownloadService;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inventory\Command\RemoveDocumentsCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inventory\Handler\RemoveDocumentsHandler;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Service\DocumentService;
use Shared\Service\DossierService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class RemoveDocumentsHandlerTest extends UnitTestCase
{
    private WooDecisionRepository&MockInterface $wooDecisionRepository;
    private LoggerInterface&MockInterface $logger;
    private BatchDownloadService&MockInterface $batchDownloadService;
    private DocumentService&MockInterface $documentService;
    private DossierService&MockInterface $dossierService;
    private RemoveDocumentsHandler $handler;

    protected function setUp(): void
    {
        $this->wooDecisionRepository = Mockery::mock(WooDecisionRepository::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->batchDownloadService = Mockery::mock(BatchDownloadService::class);
        $this->documentService = Mockery::mock(DocumentService::class);
        $this->dossierService = Mockery::mock(DossierService::class);

        $this->handler = new RemoveDocumentsHandler(
            $this->wooDecisionRepository,
            $this->logger,
            $this->batchDownloadService,
            $this->documentService,
            $this->dossierService,
        );
    }

    public function testInvokeLogsWarningWhenDossierIsNotFound(): void
    {
        $message = new RemoveDocumentsCommand(
            $dossierId = Uuid::v6(),
        );

        $this->wooDecisionRepository->expects('find')->with($dossierId)->andReturn(null);

        $this->logger->expects('warning');

        $this->handler->__invoke($message);
    }

    public function testInvokeLogsErrorForException(): void
    {
        $message = new RemoveDocumentsCommand(
            $dossierId = Uuid::v6(),
        );

        $this->wooDecisionRepository->expects('find')->with($dossierId)->andThrows(new RuntimeException('oops'));

        $this->logger->expects('error');

        $this->handler->__invoke($message);
    }

    public function testInvokeLogsWarningWhenInventoryCannotBeRemoved(): void
    {
        $message = new RemoveDocumentsCommand(
            $dossierId = Uuid::v6(),
        );

        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('canRemoveInventory')->andReturnFalse();
        $dossier->expects('isInventoryOptional')->andReturnFalse();
        $dossier->expects('getStatus')->andReturn(DossierStatus::PUBLISHED);

        $this->wooDecisionRepository->expects('find')->with($dossierId)->andReturn($dossier);

        $this->logger->expects('warning');

        $this->documentService->shouldNotReceive('removeDocumentFromDossier');
        $this->dossierService->shouldNotReceive('validateCompletion');

        $this->handler->__invoke($message);
    }

    public function testInvokeSuccessful(): void
    {
        $message = new RemoveDocumentsCommand(
            $dossierId = Uuid::v6(),
        );

        $document = Mockery::mock(Document::class);
        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('canRemoveInventory')->andReturnTrue();
        $dossier->expects('getDocuments')->andReturn(new ArrayCollection([$document]));

        $this->wooDecisionRepository->expects('find')->with($dossierId)->andReturn($dossier);

        $this->documentService->expects('removeDocumentFromDossier')->with($dossier, $document);
        $this->batchDownloadService->expects('refresh')->with(Mockery::on(
            static fn (BatchDownloadScope $scope): bool => $scope->wooDecision === $dossier,
        ));
        $this->dossierService->expects('validateCompletion')->with($dossier);

        $this->handler->__invoke($message);
    }

    public function testInvokeDoesNotRefreshBatchDownloadWhenThereAreNoDocuments(): void
    {
        $message = new RemoveDocumentsCommand(
            $dossierId = Uuid::v6(),
        );

        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('canRemoveInventory')->andReturnTrue();
        $dossier->expects('getDocuments')->andReturn(new ArrayCollection());

        $this->wooDecisionRepository->expects('find')->with($dossierId)->andReturn($dossier);

        $this->batchDownloadService->shouldNotReceive('refresh');
        $this->dossierService->expects('validateCompletion')->with($dossier);

        $this->handler->__invoke($message);
    }
}
