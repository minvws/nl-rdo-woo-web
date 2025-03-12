<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Document\Handler;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Command\WithDrawDocumentCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawService;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class WithDrawDocumentHandler
{
    public function __construct(
        private WooDecisionRepository $wooDecisionRepository,
        private DocumentRepository $documentRepository,
        private LoggerInterface $logger,
        private DossierWorkflowManager $dossierWorkflowManager,
        private DocumentWithdrawService $documentWithdrawService,
    ) {
    }

    public function __invoke(WithDrawDocumentCommand $command): void
    {
        $dossier = $this->wooDecisionRepository->find($command->dossierId);
        if ($dossier === null) {
            $this->logger->warning('No WooDecision found for this message', [
                'uuid' => $command->dossierId,
            ]);

            return;
        }

        $document = $this->documentRepository->findOneByDossierAndId($dossier, $command->documentId);
        if ($document === null) {
            $this->logger->warning('No document found for this message', [
                'dossierId' => $command->dossierId,
                'documentId' => $command->documentId,
            ]);

            return;
        }

        $this->dossierWorkflowManager->applyTransition($dossier, DossierStatusTransition::UPDATE_DOCUMENTS);

        $this->documentWithdrawService->withdraw(
            $document,
            $command->reason,
            $command->explanation,
        );
    }
}
