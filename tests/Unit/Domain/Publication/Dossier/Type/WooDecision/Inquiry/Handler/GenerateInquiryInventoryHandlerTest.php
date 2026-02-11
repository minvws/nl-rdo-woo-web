<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Handler;

use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Command\GenerateInquiryInventoryCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Handler\GenerateInquiryInventoryHandler;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\InquiryRepository;
use Shared\Service\Inventory\Sanitizer\DataProvider\InquiryInventoryDataProvider;
use Shared\Service\Inventory\Sanitizer\DataProvider\InventoryDataProviderFactory;
use Shared\Service\Inventory\Sanitizer\InventorySanitizer;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class GenerateInquiryInventoryHandlerTest extends UnitTestCase
{
    private InquiryRepository&MockInterface $inquiryRepository;
    private InventoryDataProviderFactory&MockInterface $inventoryDataProviderFactory;
    private LoggerInterface&MockInterface $logger;
    private InventorySanitizer&MockInterface $inventorySanitizer;
    private GenerateInquiryInventoryHandler $handler;

    protected function setUp(): void
    {
        $this->inquiryRepository = Mockery::mock(InquiryRepository::class);
        $this->inventoryDataProviderFactory = Mockery::mock(InventoryDataProviderFactory::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->inventorySanitizer = Mockery::mock(InventorySanitizer::class);

        $this->handler = new GenerateInquiryInventoryHandler(
            $this->inquiryRepository,
            $this->inventoryDataProviderFactory,
            $this->logger,
            $this->inventorySanitizer,
        );
    }

    public function testInvokeLogsWarningWhenInquiryIsNotFound(): void
    {
        $message = new GenerateInquiryInventoryCommand(
            $uuid = Uuid::v6(),
        );

        $this->inquiryRepository->expects('find')->with($uuid)->andReturn(null);

        $this->logger->expects('warning');

        $this->handler->__invoke($message);
    }

    public function testInvokeSuccessful(): void
    {
        $message = new GenerateInquiryInventoryCommand(
            $uuid = Uuid::v6(),
        );

        $inquiry = Mockery::mock(Inquiry::class);

        $this->inquiryRepository->expects('find')->with($uuid)->andReturn($inquiry);

        $dataProvider = Mockery::mock(InquiryInventoryDataProvider::class);

        $this->inventoryDataProviderFactory->expects('forInquiry')->with($inquiry)->andReturn($dataProvider);

        $this->inventorySanitizer->expects('generateSanitizedInventory')->with($dataProvider);

        $this->handler->__invoke($message);
    }
}
