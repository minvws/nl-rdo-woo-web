<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Handler;

use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Command\WithDrawDocumentCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawService;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\LinkedWooDecisionUpdater;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class WithDrawDocumentHandler
{
    public function __construct(
        private WooDecisionRepository $wooDecisionRepository,
        private DocumentRepository $documentRepository,
        private LoggerInterface $logger,
        private LinkedWooDecisionUpdater $linkedWooDecisionUpdater,
        private DocumentWithdrawService $documentWithdrawService,
    ) {
    }

    public function __invoke(WithDrawDocumentCommand $command): void
    {
        $wooDecision = $this->wooDecisionRepository->find($command->dossierId);
        if ($wooDecision === null) {
            $this->logger->warning('No WooDecision found for this message', [
                'uuid' => $command->dossierId,
            ]);

            return;
        }

        $document = $this->documentRepository->findOneByDossierAndId($wooDecision, $command->documentId);
        if ($document === null) {
            $this->logger->warning('No document found for this message', [
                'dossierId' => $command->dossierId,
                'documentId' => $command->documentId,
            ]);

            return;
        }

        $this->linkedWooDecisionUpdater->updateLinkedTo($wooDecision, [$document->getId()]);

        $this->documentWithdrawService->withdraw(
            $wooDecision,
            $document,
            $command->reason,
            $command->explanation,
        );
    }
}
