<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Domain\Publication\Dossier\Type\WooDecision\Repository\InquiryRepository;
use App\Message\GenerateInquiryArchivesMessage;
use App\Service\BatchDownloadService;
use App\Service\Inquiry\InquiryService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GenerateInquiryArchivesHandler
{
    public function __construct(
        private readonly InquiryRepository $inquiryRepository,
        private readonly LoggerInterface $logger,
        private readonly InquiryService $inquiryService,
        private readonly BatchDownloadService $batchDownloadService,
    ) {
    }

    public function __invoke(GenerateInquiryArchivesMessage $message): void
    {
        try {
            $inquiry = $this->inquiryRepository->find($message->getUuid());
            if (! $inquiry) {
                $this->logger->warning('No inquiry found for this message', [
                    'uuid' => $message->getUuid(),
                ]);

                return;
            }

            $this->batchDownloadService->removeAllDownloadsForEntity($inquiry);

            // Generate an archive for all documents directly linked to the inquiry
            $this->inquiryService->generateBatch(
                $inquiry,
                $this->inquiryRepository->getDocumentsForPubliclyAvailableDossiers($inquiry)
            );

            // Generate an archive for all documents directly linked to the inquiry for each dossier
            foreach ($inquiry->getDossiers() as $dossier) {
                $this->inquiryService->generateBatch(
                    $inquiry,
                    $this->inquiryRepository->getDocsForInquiryDossierQueryBuilder($inquiry, $dossier)
                );
            }
        } catch (\Exception $exception) {
            $this->logger->error('Failed to generate inventory for inquiry', [
                'id' => $message->getUuid(),
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
