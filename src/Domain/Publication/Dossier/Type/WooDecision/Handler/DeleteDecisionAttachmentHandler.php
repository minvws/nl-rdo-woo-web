<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Handler;

use App\Domain\Publication\Dossier\Type\WooDecision\Command\DeleteDecisionAttachmentCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Event\DecisionAttachmentDeletedEvent;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Repository\DecisionAttachmentRepository;
use App\Repository\DossierRepository;
use App\Service\Storage\DocumentStorageService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
readonly class DeleteDecisionAttachmentHandler
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private DossierWorkflowManager $dossierWorkflowManager,
        private DecisionAttachmentRepository $decisionAttachmentRepository,
        private DossierRepository $dossierRepository,
        private DocumentStorageService $documentStorage,
    ) {
    }

    public function __invoke(DeleteDecisionAttachmentCommand $command): void
    {
        $dossierId = $command->dossierId;
        $dossier = $this->dossierRepository->findOneByDossierId($dossierId);

        $decisionAttachment = $this->decisionAttachmentRepository->findOneOrNullForDossier($dossierId, $command->decisionAttachmentId);
        if ($decisionAttachment === null) {
            throw new DecisionAttachmentNotFoundException();
        }

        $this->dossierWorkflowManager->applyTransition($dossier, DossierStatusTransition::DELETE_DECISION_ATTACHMENT);

        $this->documentStorage->removeFileForEntity($decisionAttachment);
        $this->decisionAttachmentRepository->remove($decisionAttachment, true);

        $this->messageBus->dispatch(
            new DecisionAttachmentDeletedEvent($dossier, $decisionAttachment)
        );
    }
}
