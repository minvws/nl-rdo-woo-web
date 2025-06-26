<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment\Handler;

use App\Domain\Publication\Attachment\AttachmentDispatcher;
use App\Domain\Publication\Attachment\Command\UpdateAttachmentCommand;
use App\Domain\Publication\Attachment\Entity\AbstractAttachment;
use App\Domain\Publication\Attachment\Repository\AttachmentRepository;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Upload\Process\EntityUploadStorer;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsMessageHandler]
readonly class UpdateAttachmentHandler
{
    public function __construct(
        private AttachmentRepository $attachmentRepository,
        private ValidatorInterface $validator,
        private AttachmentEntityLoader $entityLoader,
        private AttachmentDispatcher $dispatcher,
        private EntityUploadStorer $uploadStorer,
    ) {
    }

    public function __invoke(UpdateAttachmentCommand $command): AbstractAttachment
    {
        $entity = $this->entityLoader->loadAndValidateAttachment(
            $command->dossierId,
            $command->attachmentId,
            DossierStatusTransition::UPDATE_ATTACHMENT,
        );

        $this->mapProperties($command, $entity);

        $violations = $this->validator->validate($entity);
        if ($violations->count() > 0) {
            throw new ValidationFailedException($entity, $violations);
        }

        $this->mapUpload($command, $entity);

        $this->attachmentRepository->save($entity, true);

        $this->dispatcher->dispatchAttachmentUpdatedEvent($entity);

        return $entity;
    }

    private function mapProperties(UpdateAttachmentCommand $command, AbstractAttachment $entity): void
    {
        if ($command->formalDate !== null) {
            $entity->setFormalDate($command->formalDate);
        }

        if ($command->type !== null) {
            $entity->setType($command->type);
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
    }

    private function mapUpload(UpdateAttachmentCommand $command, AbstractAttachment $entity): void
    {
        if ($command->uploadFileReference !== null) {
            $this->uploadStorer->storeUploadForEntityWithSourceTypeAndName(
                $entity,
                $command->uploadFileReference
            );
        }
    }
}
