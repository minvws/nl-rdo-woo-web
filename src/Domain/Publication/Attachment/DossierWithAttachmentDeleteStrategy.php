<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Attachment;

use Shared\Domain\Publication\Attachment\Command\DeleteAttachmentCommand;
use Shared\Domain\Publication\Attachment\Command\DeleteAttachmentWithOverrideCommand;
use Shared\Domain\Publication\Attachment\Entity\EntityWithAttachments;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\DossierDeleteStrategyInterface;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class DossierWithAttachmentDeleteStrategy implements DossierDeleteStrategyInterface
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function delete(AbstractDossier $dossier): void
    {
        if (! $dossier instanceof EntityWithAttachments) {
            return;
        }

        foreach ($dossier->getAttachments() as $attachment) {
            $this->messageBus->dispatch(
                new DeleteAttachmentCommand($dossier->getId(), $attachment->getId())
            );
        }
    }

    public function deleteWithOverride(AbstractDossier $dossier): void
    {
        if (! $dossier instanceof EntityWithAttachments) {
            return;
        }

        foreach ($dossier->getAttachments() as $attachment) {
            $this->messageBus->dispatch(
                new DeleteAttachmentWithOverrideCommand($dossier->getId(), $attachment->getId())
            );
        }
    }
}
