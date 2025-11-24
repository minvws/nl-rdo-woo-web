<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Handler;

use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Command\RemoveDocumentCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Service\DocumentService;
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
