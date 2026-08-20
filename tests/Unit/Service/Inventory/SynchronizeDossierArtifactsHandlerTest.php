<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Inventory;

use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\Dossier\Command\SynchronizeDossierArtifactsCommand;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\Type\Advice\Advice;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Service\Inventory\InventoryUpdater;
use Shared\Service\Inventory\SynchronizeDossierArtifactsHandler;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class SynchronizeDossierArtifactsHandlerTest extends UnitTestCase
{
    private DossierRepository&MockInterface $dossierRepository;
    private LoggerInterface&MockInterface $logger;
    private InventoryUpdater&MockInterface $inventoryUpdater;
    private SynchronizeDossierArtifactsHandler $handler;

    protected function setUp(): void
    {
        $this->dossierRepository = Mockery::mock(DossierRepository::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->inventoryUpdater = Mockery::mock(InventoryUpdater::class);

        $this->handler = new SynchronizeDossierArtifactsHandler(
            $this->dossierRepository,
            $this->logger,
            $this->inventoryUpdater,
        );

        parent::setUp();
    }

    public function testInvokeReturnsEarlyWhenNoDossierFound(): void
    {
        $uuid = Uuid::v6();

        $this->dossierRepository->expects('find')->with($uuid)->andReturnNull();
        $this->logger->expects('warning');

        $this->inventoryUpdater->expects('updateWooDecisionInventories')->never();

        $command = new SynchronizeDossierArtifactsCommand($uuid);
        $this->handler->__invoke($command);
    }

    public function testInvokeReturnsEarlyWhenNonWooDecisionDossierFound(): void
    {
        $uuid = Uuid::v6();
        $advice = Mockery::mock(Advice::class);

        $this->dossierRepository->expects('find')->with($uuid)->andReturn($advice);
        $this->logger->expects('warning')->never();
        $this->inventoryUpdater->expects('updateWooDecisionInventories')->never();

        $command = new SynchronizeDossierArtifactsCommand($uuid);
        $this->handler->__invoke($command);
    }

    public function testInvokeCallsInventoryServiceForWooDecision(): void
    {
        $uuid = Uuid::v6();
        $wooDecision = Mockery::mock(WooDecision::class);

        $this->dossierRepository->expects('find')->with($uuid)->andReturn($wooDecision);
        $this->inventoryUpdater->expects('updateWooDecisionInventories')->with($wooDecision);

        $command = new SynchronizeDossierArtifactsCommand($uuid);
        $this->handler->__invoke($command);
    }
}
