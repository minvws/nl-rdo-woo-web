<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\GenerateInquiryInventoryMessage;
use App\Repository\DocumentRepository;
use App\Repository\InquiryRepository;
use App\Service\Inventory\Sanitizer\InquiryInventoryDataProvider;
use App\Service\Inventory\Sanitizer\InventorySanitizer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GenerateInquiryInventoryHandler
{
    public function __construct(
        private readonly InquiryRepository $inquiryRepository,
        private readonly DocumentRepository $documentRepository,
        private readonly LoggerInterface $logger,
        private readonly InventorySanitizer $inventorySanitizer,
    ) {
    }

    public function __invoke(GenerateInquiryInventoryMessage $message): void
    {
        try {
            $inquiry = $this->inquiryRepository->find($message->getUuid());
            if (! $inquiry) {
                $this->logger->warning('No inquiry found for this message', [
                    'uuid' => $message->getUuid(),
                ]);

                return;
            }

            $dataProvider = new InquiryInventoryDataProvider(
                $inquiry,
                $this->documentRepository->getAllInquiryDocumentsWithDossiers($inquiry)
            );

            $this->inventorySanitizer->generateSanitizedInventory($dataProvider);
        } catch (\Exception $exception) {
            $this->logger->error('Failed to generate inventory for inquiry', [
                'id' => $message->getUuid(),
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
