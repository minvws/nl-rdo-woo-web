<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Attachment\Handler;

use Shared\Domain\Publication\Attachment\AttachmentDeleter;
use Shared\Domain\Publication\Attachment\Command\DeleteAttachmentCommand;
use Shared\Domain\Publication\Attachment\Command\DeleteAttachmentWithOverrideCommand;
use Shared\Domain\Publication\Attachment\Event\AttachmentDeletedEvent;
use Shared\Domain\Publication\Attachment\Repository\AttachmentRepository;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
readonly class DeleteAttachmentHandler
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private AttachmentRepository $attachmentRepository,
        private AttachmentEntityLoader $entityLoader,
        private AttachmentDeleter $deleter,
    ) {
    }

    public function __invoke(DeleteAttachmentCommand|DeleteAttachmentWithOverrideCommand $command): void
    {
        $entity = $command instanceof DeleteAttachmentWithOverrideCommand
            ? $this->entityLoader->loadAttachment($command->dossierId, $command->attachmentId)
            : $this->entityLoader->loadAndValidateAttachment(
                $command->dossierId,
                $command->attachmentId,
                DossierStatusTransition::DELETE_ATTACHMENT,
            );

        $this->deleter->delete($entity);

        $event = AttachmentDeletedEvent::forAttachment($entity);

        $this->attachmentRepository->remove($entity, true);

        if ($entity->getDossier()->getStatus()->isNotDeleted()) {
            $this->messageBus->dispatch($event);
        }
    }
}
