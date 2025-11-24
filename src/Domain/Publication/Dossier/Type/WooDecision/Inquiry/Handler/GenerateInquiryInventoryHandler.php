<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Handler;

use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Command\GenerateInquiryInventoryCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\InquiryRepository;
use Shared\Service\Inventory\Sanitizer\DataProvider\InventoryDataProviderFactory;
use Shared\Service\Inventory\Sanitizer\InventorySanitizer;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class GenerateInquiryInventoryHandler
{
    public function __construct(
        private InquiryRepository $inquiryRepository,
        private InventoryDataProviderFactory $dataProviderFactory,
        private LoggerInterface $logger,
        private InventorySanitizer $inventorySanitizer,
    ) {
    }

    public function __invoke(GenerateInquiryInventoryCommand $message): void
    {
        $inquiry = $this->inquiryRepository->find($message->getUuid());
        if (! $inquiry) {
            $this->logger->warning('No inquiry found for this message', [
                'uuid' => $message->getUuid(),
            ]);

            return;
        }

        $this->inventorySanitizer->generateSanitizedInventory(
            $this->dataProviderFactory->forInquiry($inquiry)
        );
    }
}
