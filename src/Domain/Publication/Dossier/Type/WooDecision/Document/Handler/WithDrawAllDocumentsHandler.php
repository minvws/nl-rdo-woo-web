<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Handler;

use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Command\WithDrawAllDocumentsCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawService;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\LinkedWooDecisionUpdater;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
readonly class WithDrawAllDocumentsHandler
{
    public function __construct(
        private WooDecisionRepository $repository,
        private LoggerInterface $logger,
        private LinkedWooDecisionUpdater $linkedWooDecisionUpdater,
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

        $this->linkedWooDecisionUpdater->updateLinkedTo(
            $dossier,
            $dossier->getDocuments()->map(static fn (Document $document): Uuid => $document->getId())->getValues(),
        );

        $this->documentWithdrawService->withDrawAllDocuments(
            $dossier,
            $command->reason,
            $command->explanation,
        );
    }
}
