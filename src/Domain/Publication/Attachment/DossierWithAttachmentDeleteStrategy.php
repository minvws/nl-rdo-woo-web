<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment;

use App\Domain\Publication\Attachment\Command\DeleteAttachmentCommand;
use App\Domain\Publication\Attachment\Command\DeleteAttachmentWithOverrideCommand;
use App\Domain\Publication\Attachment\Entity\EntityWithAttachments;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\DossierDeleteStrategyInterface;
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
