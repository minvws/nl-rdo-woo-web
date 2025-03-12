<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment;

use App\Domain\Publication\Attachment\Command\WithDrawAttachmentCommand;
use App\Domain\Publication\Attachment\Entity\AbstractAttachment;
use App\Domain\Publication\Attachment\Enum\AttachmentWithdrawReason;
use App\Domain\Publication\Attachment\Event\AttachmentCreatedEvent;
use App\Domain\Publication\Attachment\Event\AttachmentUpdatedEvent;
use App\Domain\Publication\Attachment\Event\AttachmentWithdrawnEvent;
use App\Domain\Publication\Dossier\AbstractDossier;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class AttachmentDispatcher
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function dispatchAttachmentUpdatedEvent(AbstractAttachment $entity): void
    {
        $this->messageBus->dispatch(
            AttachmentUpdatedEvent::forAttachment($entity),
        );
    }

    public function dispatchAttachmentCreatedEvent(AbstractAttachment $entity): void
    {
        $this->messageBus->dispatch(
            AttachmentCreatedEvent::forAttachment($entity),
        );
    }

    public function dispatchAttachmentWithdrawnEvent(AbstractAttachment $entity): void
    {
        $this->messageBus->dispatch(
            AttachmentWithdrawnEvent::forAttachment($entity),
        );
    }

    public function dispatchWithdrawAttachmentCommand(
        AbstractDossier $dossier,
        AbstractAttachment $attachment,
        AttachmentWithdrawReason $reason,
        string $explanation,
    ): void {
        $this->messageBus->dispatch(
            new WithDrawAttachmentCommand(
                $dossier->getId(),
                $attachment->getId(),
                $reason,
                $explanation
            ),
        );
    }
}
