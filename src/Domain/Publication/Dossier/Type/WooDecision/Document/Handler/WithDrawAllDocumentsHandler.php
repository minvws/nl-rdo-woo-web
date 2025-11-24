<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Handler;

use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Command\WithDrawAllDocumentsCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawService;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
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
