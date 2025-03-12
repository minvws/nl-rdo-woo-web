<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Handler;

use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Command\GenerateInquiryInventoryCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\InquiryRepository;
use App\Service\Inventory\Sanitizer\DataProvider\InventoryDataProviderFactory;
use App\Service\Inventory\Sanitizer\InventorySanitizer;
use Psr\Log\LoggerInterface;
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
