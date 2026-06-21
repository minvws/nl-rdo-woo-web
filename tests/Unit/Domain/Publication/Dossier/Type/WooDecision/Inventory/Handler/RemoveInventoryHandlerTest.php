<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Inventory\Handler;

use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shared\Domain\Publication\BatchDownload\BatchDownloadScope;
use Shared\Domain\Publication\BatchDownload\BatchDownloadService;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inventory\Command\RemoveInventoryCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inventory\Handler\RemoveInventoryHandler;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Service\DossierService;
use Shared\Service\Inventory\InventoryService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class RemoveInventoryHandlerTest extends UnitTestCase
{
    private WooDecisionRepository&MockInterface $wooDecisionRepository;
    private LoggerInterface&MockInterface $logger;
    private InventoryService&MockInterface $inventoryService;
    private BatchDownloadService&MockInterface $batchDownloadService;
    private DossierService&MockInterface $dossierService;
    private RemoveInventoryHandler $handler;

    protected function setUp(): void
    {
        $this->wooDecisionRepository = Mockery::mock(WooDecisionRepository::class);
        $this->inventoryService = Mockery::mock(InventoryService::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->batchDownloadService = Mockery::mock(BatchDownloadService::class);
        $this->dossierService = Mockery::mock(DossierService::class);

        $this->handler = new RemoveInventoryHandler(
            $this->wooDecisionRepository,
            $this->inventoryService,
            $this->logger,
            $this->batchDownloadService,
            $this->dossierService,
        );
    }

    public function testInvokeLogsWarningWhenDossierIsNotFound(): void
    {
        $message = new RemoveInventoryCommand(
            $dossierId = Uuid::v6(),
        );

        $this->wooDecisionRepository->expects('find')->with($dossierId)->andReturn(null);

        $this->logger->expects('warning');

        $this->handler->__invoke($message);
    }

    public function testInvokeLogsErrorForException(): void
    {
        $message = new RemoveInventoryCommand(
            $dossierId = Uuid::v6(),
        );

        $this->wooDecisionRepository->expects('find')->with($dossierId)->andThrows(new RuntimeException('oops'));

        $this->logger->expects('error');

        $this->handler->__invoke($message);
    }

    public function testInvokeLogsWarningWhenInventoryCannotBeRemoved(): void
    {
        $message = new RemoveInventoryCommand(
            $dossierId = Uuid::v6(),
        );

        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('canRemoveInventory')->andReturnFalse();
        $dossier->expects('isInventoryOptional')->andReturnFalse();
        $dossier->expects('getStatus')->andReturn(DossierStatus::PUBLISHED);

        $this->wooDecisionRepository->expects('find')->with($dossierId)->andReturn($dossier);

        $this->logger->expects('warning');

        $this->inventoryService->shouldNotReceive('removeInventories');
        $this->dossierService->shouldNotReceive('validateCompletion');

        $this->handler->__invoke($message);
    }

    public function testInvokeSuccessful(): void
    {
        $message = new RemoveInventoryCommand(
            $dossierId = Uuid::v6(),
        );

        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('canRemoveInventory')->andReturnTrue();

        $this->wooDecisionRepository->expects('find')->with($dossierId)->andReturn($dossier);

        $this->inventoryService->expects('removeInventories')->with($dossier)->andReturnTrue();
        $this->batchDownloadService->expects('refresh')->with(Mockery::on(
            static fn (BatchDownloadScope $scope): bool => $scope->wooDecision === $dossier,
        ));
        $this->dossierService->expects('validateCompletion')->with($dossier);

        $this->handler->__invoke($message);
    }

    public function testInvokeDoesNotRefreshBatchDownloadWhenNoInventoriesAreRemoved(): void
    {
        $message = new RemoveInventoryCommand(
            $dossierId = Uuid::v6(),
        );

        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('canRemoveInventory')->andReturnTrue();

        $this->wooDecisionRepository->expects('find')->with($dossierId)->andReturn($dossier);

        $this->inventoryService->expects('removeInventories')->with($dossier)->andReturnFalse();
        $this->batchDownloadService->shouldNotReceive('refresh');
        $this->dossierService->expects('validateCompletion')->with($dossier);

        $this->handler->__invoke($message);
    }
}
