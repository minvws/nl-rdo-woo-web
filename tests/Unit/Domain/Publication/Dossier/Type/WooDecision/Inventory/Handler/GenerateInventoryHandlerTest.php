<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Inventory\Handler;

use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inventory\Command\GenerateInventoryCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inventory\Handler\GenerateInventoryHandler;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Service\Inventory\Sanitizer\DataProvider\InventoryDataProviderFactory;
use Shared\Service\Inventory\Sanitizer\DataProvider\WooDecisionInventoryDataProvider;
use Shared\Service\Inventory\Sanitizer\InventorySanitizer;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class GenerateInventoryHandlerTest extends UnitTestCase
{
    private WooDecisionRepository&MockInterface $wooDecisionRepository;
    private InventoryDataProviderFactory&MockInterface $inventoryDataProviderFactory;
    private LoggerInterface&MockInterface $logger;
    private InventorySanitizer&MockInterface $inventorySanitizer;
    private GenerateInventoryHandler $handler;

    protected function setUp(): void
    {
        $this->wooDecisionRepository = Mockery::mock(WooDecisionRepository::class);
        $this->inventoryDataProviderFactory = Mockery::mock(InventoryDataProviderFactory::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->inventorySanitizer = Mockery::mock(InventorySanitizer::class);

        $this->handler = new GenerateInventoryHandler(
            $this->wooDecisionRepository,
            $this->inventoryDataProviderFactory,
            $this->logger,
            $this->inventorySanitizer,
        );
    }

    public function testInvokeLogsWarningWhenDossierIsNotFound(): void
    {
        $message = new GenerateInventoryCommand(
            $uuid = Uuid::v6(),
        );

        $this->wooDecisionRepository->expects('find')->with($uuid)->andReturn(null);

        $this->logger->expects('warning');

        $this->handler->__invoke($message);
    }

    public function testInvokeSuccessful(): void
    {
        $message = new GenerateInventoryCommand(
            $uuid = Uuid::v6(),
        );

        $dossier = Mockery::mock(WooDecision::class);

        $this->wooDecisionRepository->expects('find')->with($uuid)->andReturn($dossier);

        $dataProvider = Mockery::mock(WooDecisionInventoryDataProvider::class);

        $this->inventoryDataProviderFactory->expects('forWooDecision')->with($dossier)->andReturn($dataProvider);

        $this->inventorySanitizer->expects('generateSanitizedInventory')->with($dataProvider);

        $this->handler->__invoke($message);
    }
}
