<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Handler;

use App\Domain\Publication\Dossier\Type\WooDecision\Command\CreateDecisionAttachmentCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Event\DecisionAttachmentCreatedEvent;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Entity\DecisionAttachment;
use App\Repository\DecisionAttachmentRepository;
use App\Repository\DossierRepository;
use App\Service\Uploader\UploaderService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsMessageHandler]
readonly class CreateDecisionAttachmentHandler
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private DossierWorkflowManager $dossierWorkflowManager,
        private DecisionAttachmentRepository $decisionAttachmentRepository,
        private DossierRepository $dossierRepository,
        private UploaderService $uploaderService,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(CreateDecisionAttachmentCommand $command): DecisionAttachment
    {
        $dossierId = $command->dossierId;
        $dossier = $this->dossierRepository->findOneByDossierId($dossierId);

        $this->dossierWorkflowManager->applyTransition($dossier, DossierStatusTransition::UPDATE_DECISION);

        $decisionAttachment = new DecisionAttachment(
            dossier: $dossier,
            formalDate: $command->formalDate,
            type: $command->type,
            language: $command->language,
        );

        $decisionAttachment->setInternalReference($command->internalReference);
        $decisionAttachment->setGrounds($command->grounds);
        $decisionAttachment->getFileInfo()->setName($command->name);

        $violations = $this->validator->validate($decisionAttachment);
        if ($violations->count() > 0) {
            throw new ValidationFailedException($decisionAttachment, $violations);
        }

        $this->uploaderService->attachFileToEntity(
            $command->uploadFileReference,
            $decisionAttachment,
            $decisionAttachment->getUploadGroupId(),
        );

        $this->decisionAttachmentRepository->save($decisionAttachment, true);

        $this->messageBus->dispatch(
            new DecisionAttachmentCreatedEvent($decisionAttachment),
        );

        return $decisionAttachment;
    }
}
