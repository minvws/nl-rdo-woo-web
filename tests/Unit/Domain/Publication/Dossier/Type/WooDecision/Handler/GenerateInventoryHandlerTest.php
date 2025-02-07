<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Handler;

use App\Domain\Publication\Dossier\Type\WooDecision\Command\GenerateInventoryCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\Handler\GenerateInventoryHandler;
use App\Domain\Publication\Dossier\Type\WooDecision\Repository\WooDecisionRepository;
use App\Service\Inventory\Sanitizer\DataProvider\InventoryDataProviderFactory;
use App\Service\Inventory\Sanitizer\DataProvider\WooDecisionInventoryDataProvider;
use App\Service\Inventory\Sanitizer\InventorySanitizer;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

class GenerateInventoryHandlerTest extends UnitTestCase
{
    private WooDecisionRepository&MockInterface $wooDecisionRepository;
    private InventoryDataProviderFactory&MockInterface $inventoryDataProviderFactory;
    private LoggerInterface&MockInterface $logger;
    private InventorySanitizer&MockInterface $inventorySanitizer;
    private GenerateInventoryHandler $handler;

    public function setUp(): void
    {
        $this->wooDecisionRepository = \Mockery::mock(WooDecisionRepository::class);
        $this->inventoryDataProviderFactory = \Mockery::mock(InventoryDataProviderFactory::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->inventorySanitizer = \Mockery::mock(InventorySanitizer::class);

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

        $dossier = \Mockery::mock(WooDecision::class);

        $this->wooDecisionRepository->expects('find')->with($uuid)->andReturn($dossier);

        $dataProvider = \Mockery::mock(WooDecisionInventoryDataProvider::class);

        $this->inventoryDataProviderFactory->expects('forWooDecision')->with($dossier)->andReturn($dataProvider);

        $this->inventorySanitizer->expects('generateSanitizedInventory')->with($dataProvider);

        $this->handler->__invoke($message);
    }
}
