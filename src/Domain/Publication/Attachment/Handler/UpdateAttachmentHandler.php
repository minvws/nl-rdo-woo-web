<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Attachment\Handler;

use Shared\Domain\Publication\Attachment\AttachmentDispatcher;
use Shared\Domain\Publication\Attachment\Command\UpdateAttachmentCommand;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Repository\AttachmentRepository;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Shared\Domain\Upload\Process\EntityUploadStorer;
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

        $metadataBefore = $entity->getMetadataSnapshot();
        $this->mapProperties($command, $entity);
        $metadataUpdated = $metadataBefore !== $entity->getMetadataSnapshot();

        $violations = $this->validator->validate($entity);
        if ($violations->count() > 0) {
            throw new ValidationFailedException($entity, $violations);
        }

        $this->mapUpload($command, $entity);

        $this->attachmentRepository->save($entity, true);

        $fileUpdated = $command->uploadFileReference !== null;

        match (true) {
            $fileUpdated && $metadataUpdated => $this->dispatcher->dispatchAttachmentMetadataAndFileUpdatedEvent($entity),
            $fileUpdated => $this->dispatcher->dispatchAttachmentFileUpdatedEvent($entity),
            $metadataUpdated => $this->dispatcher->dispatchAttachmentMetadataUpdatedEvent($entity),
            default => null,
        };

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
                $command->uploadFileReference,
            );
        }
    }
}
