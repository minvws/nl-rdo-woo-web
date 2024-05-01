<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Covenant\Handler\CovenantAttachment;

use App\Domain\Publication\Dossier\Type\Covenant\Command\CreateCovenantAttachmentCommand;
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
readonly class CreateCovenantAttachmentHandler
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private DossierWorkflowManager $dossierWorkflowManager,
        private CovenantAttachmentRepository $covenantAttachmentRepository,
        private CovenantRepository $dossierRepository,
        private UploaderService $uploaderService,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(CreateCovenantAttachmentCommand $command): CovenantAttachment
    {
        $dossierId = $command->dossierId;
        $dossier = $this->dossierRepository->findOneByDossierId($dossierId);

        $this->dossierWorkflowManager->applyTransition($dossier, DossierStatusTransition::UPDATE_CONTENT);

        $entity = new CovenantAttachment(
            dossier: $dossier,
            formalDate: $command->formalDate,
            type: $command->type,
            language: $command->language,
        );

        $entity->setInternalReference($command->internalReference);
        $entity->setGrounds($command->grounds);
        $entity->getFileInfo()->setName($command->name);

        $violations = $this->validator->validate($entity);
        if ($violations->count() > 0) {
            throw new ValidationFailedException($entity, $violations);
        }

        $this->uploaderService->attachFileToEntity(
            $command->uploadFileReference,
            $entity,
            $entity->getUploadGroupId(),
        );

        $this->covenantAttachmentRepository->save($entity, true);

        $this->messageBus->dispatch(
            new CovenantAttachmentUpdatedEvent($entity)
        );

        return $entity;
    }
}
