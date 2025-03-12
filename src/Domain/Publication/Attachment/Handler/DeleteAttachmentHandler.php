<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment\Handler;

use App\Domain\Publication\Attachment\AttachmentDeleter;
use App\Domain\Publication\Attachment\Command\DeleteAttachmentCommand;
use App\Domain\Publication\Attachment\Event\AttachmentDeletedEvent;
use App\Domain\Publication\Attachment\Repository\AttachmentRepository;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
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

    public function __invoke(DeleteAttachmentCommand $command): void
    {
        $entity = $this->entityLoader->loadAndValidateAttachment(
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
