<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Document\Handler;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Command\WithDrawAllDocumentsCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawService;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class WithDrawAllDocumentsHandler
{
    public function __construct(
        private WooDecisionRepository $repository,
        private LoggerInterface $logger,
        private DossierWorkflowManager $dossierWorkflowManager,
        private DocumentWithdrawService $documentWithdrawService,
    ) {
    }

    public function __invoke(WithDrawAllDocumentsCommand $command): void
    {
        $dossier = $this->repository->find($command->dossierId);
        if ($dossier === null) {
            $this->logger->warning('No WooDecision found for this message', [
                'uuid' => $command->dossierId,
            ]);

            return;
        }

        $this->dossierWorkflowManager->applyTransition($dossier, DossierStatusTransition::UPDATE_DOCUMENTS);

        $this->documentWithdrawService->withDrawAllDocuments(
            $dossier,
            $command->reason,
            $command->explanation,
        );
    }
}
