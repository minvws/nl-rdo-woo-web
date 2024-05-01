<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Covenant\Handler\CovenantAttachment;

use App\Domain\Publication\Dossier\Type\Covenant\Command\UpdateCovenantAttachmentCommand;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachmentRepository;
use App\Domain\Publication\Dossier\Type\Covenant\Event\CovenantAttachmentUpdatedEvent;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Repository\CovenantRepository;
use App\Service\Uploader\UploaderService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsMessageHandler]
readonly class UpdateCovenantAttachmentHandler
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private DossierWorkflowManager $dossierWorkflowManager,
        private CovenantAttachmentRepository $covenantAttachmentRepository,
        private CovenantRepository $dossierRepository,
        private ValidatorInterface $validator,
        private UploaderService $uploaderService,
    ) {
    }

    public function __invoke(UpdateCovenantAttachmentCommand $command): CovenantAttachment
    {
        $dossierId = $command->dossierId;
        $dossier = $this->dossierRepository->findOneByDossierId($dossierId);

        $entity = $this->covenantAttachmentRepository->findOneOrNullForDossier($dossierId, $command->attachmentId);
        if ($entity === null) {
            throw new CovenantAttachmentNotFoundException();
        }

        $this->dossierWorkflowManager->applyTransition($dossier, DossierStatusTransition::UPDATE_CONTENT);

        $this->mapProperties($command, $entity);

        $violations = $this->validator->validate($entity);
        if ($violations->count() > 0) {
            throw new ValidationFailedException($entity, $violations);
        }

        $this->mapUpload($command, $entity);

        $this->covenantAttachmentRepository->save($entity, true);

        $this->messageBus->dispatch(
            new CovenantAttachmentUpdatedEvent($entity)
        );

        return $entity;
    }

    private function mapProperties(UpdateCovenantAttachmentCommand $command, CovenantAttachment $entity): void
    {
        if ($command->formalDate !== null) {
            $entity->setFormalDate($command->formalDate);
        }

        if ($command->type !== null) {
            $entity->setType($command->type);
        }

        if ($command->name !== null) {
            $entity->getFileInfo()->setName($command->name);
        }

        if ($command->language !== null) {
            $entity->setLanguage($command->language);
        }

        if ($command->internalReference !== null) {
            $entity->setInternalReference($command->internalReference);
        }

        if ($command->grounds !== null) {
            $entity->setGrounds($command->grounds);
        }

        if ($command->name !== null) {
            $entity->getFileInfo()->setName($command->name);
        }
    }

    private function mapUpload(UpdateCovenantAttachmentCommand $command, CovenantAttachment $entity): void
    {
        if ($command->uploadFileReference !== null) {
            $this->uploaderService->attachFileToEntity(
                $command->uploadFileReference,
                $entity,
                $entity->getUploadGroupId(),
            );
        }
    }
}
