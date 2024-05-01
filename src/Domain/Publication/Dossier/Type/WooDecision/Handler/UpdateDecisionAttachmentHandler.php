<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Handler;

use App\Domain\Publication\Dossier\Type\WooDecision\Command\UpdateDecisionAttachmentCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Event\DecisionAttachmentUpdatedEvent;
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
readonly class UpdateDecisionAttachmentHandler
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private DossierWorkflowManager $dossierWorkflowManager,
        private DecisionAttachmentRepository $decisionAttachmentRepository,
        private DossierRepository $dossierRepository,
        private ValidatorInterface $validator,
        private UploaderService $uploaderService,
    ) {
    }

    public function __invoke(UpdateDecisionAttachmentCommand $command): DecisionAttachment
    {
        $dossierId = $command->dossierId;
        $dossier = $this->dossierRepository->findOneByDossierId($dossierId);

        $decisionAttachment = $this->decisionAttachmentRepository->findOneOrNullForDossier($dossierId, $command->decisionAttachmentId);
        if ($decisionAttachment === null) {
            throw new DecisionAttachmentNotFoundException();
        }

        $this->dossierWorkflowManager->applyTransition($dossier, DossierStatusTransition::UPDATE_DECISION);

        $this->mapProperties($command, $decisionAttachment);

        $violations = $this->validator->validate($decisionAttachment);
        if ($violations->count() > 0) {
            throw new ValidationFailedException($decisionAttachment, $violations);
        }

        $this->mapUpload($command, $decisionAttachment);

        $this->decisionAttachmentRepository->save($decisionAttachment, true);

        $this->messageBus->dispatch(
            new DecisionAttachmentUpdatedEvent($decisionAttachment)
        );

        return $decisionAttachment;
    }

    private function mapProperties(UpdateDecisionAttachmentCommand $command, DecisionAttachment $decisionAttachment): void
    {
        if ($command->formalDate !== null) {
            $decisionAttachment->setFormalDate($command->formalDate);
        }

        if ($command->type !== null) {
            $decisionAttachment->setType($command->type);
        }

        if ($command->name !== null) {
            $decisionAttachment->getFileInfo()->setName($command->name);
        }

        if ($command->language !== null) {
            $decisionAttachment->setLanguage($command->language);
        }

        if ($command->internalReference !== null) {
            $decisionAttachment->setInternalReference($command->internalReference);
        }

        if ($command->grounds !== null) {
            $decisionAttachment->setGrounds($command->grounds);
        }

        if ($command->name !== null) {
            $decisionAttachment->getFileInfo()->setName($command->name);
        }
    }

    private function mapUpload(UpdateDecisionAttachmentCommand $command, DecisionAttachment $decisionAttachment): void
    {
        if ($command->uploadFileReference !== null) {
            $this->uploaderService->attachFileToEntity(
                $command->uploadFileReference,
                $decisionAttachment,
                $decisionAttachment->getUploadGroupId(),
            );
        }
    }
}
