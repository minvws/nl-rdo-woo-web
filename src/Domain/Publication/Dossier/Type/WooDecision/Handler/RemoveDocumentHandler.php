<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Handler;

use App\Domain\Publication\Dossier\Type\WooDecision\Command\RemoveDocumentCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Repository\DocumentRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\Repository\WooDecisionRepository;
use App\Service\DocumentService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RemoveDocumentHandler
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly WooDecisionRepository $wooDecisionRepository,
        private readonly DocumentRepository $documentRepository,
        private readonly DocumentService $documentService,
    ) {
    }

    public function __invoke(RemoveDocumentCommand $message): void
    {
        $dossier = $this->wooDecisionRepository->find($message->getDossierId());
        if (! $dossier) {
            $this->logger->warning('No dossier found for this message', [
                'uuid' => $message->getDossierId(),
            ]);

            return;
        }

        $document = $this->documentRepository->find($message->getDocumentId());
        if (! $document) {
            $this->logger->warning('No document found for this message', [
                'uuid' => $message->getDocumentId(),
            ]);

            return;
        }

        $this->documentService->removeDocumentFromDossier($dossier, $document);
    }
}
