<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Handler;

use App\Domain\Publication\Dossier\Type\WooDecision\Command\UpdateInquiryLinksCommand;
use App\Repository\OrganisationRepository;
use App\Service\Inquiry\InquiryService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateInquiryLinksHandler
{
    public function __construct(
        private readonly OrganisationRepository $organisationRepo,
        private readonly LoggerInterface $logger,
        private readonly InquiryService $inquiryService,
    ) {
    }

    public function __invoke(UpdateInquiryLinksCommand $message): void
    {
        try {
            $organisation = $this->organisationRepo->find($message->getOrganisationId());
            if (! $organisation) {
                $this->logger->warning('No organisation found for this message', [
                    'uuid' => $message->getOrganisationId(),
                ]);

                return;
            }

            $this->inquiryService->updateInquiryLinks(
                $organisation,
                $message->getCaseNr(),
                $message->getDocIdsToAdd(),
                $message->getDocIdsToDelete(),
                $message->getDossierIdsToAdd(),
            );
        } catch (\Exception $e) {
            $this->logger->error('Failed to update inquiry links', [
                'casenr' => $message->getCaseNr(),
                'adds' => $message->getDocIdsToAdd(),
                'deletes' => $message->getDocIdsToDelete(),
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
