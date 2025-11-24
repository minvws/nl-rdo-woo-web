<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Attachment\Handler;

use Shared\Domain\Publication\Attachment\AttachmentDeleter;
use Shared\Domain\Publication\Attachment\AttachmentDispatcher;
use Shared\Domain\Publication\Attachment\Command\WithDrawAttachmentCommand;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Exception\AttachmentWithdrawException;
use Shared\Domain\Publication\Attachment\Repository\AttachmentRepository;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class WithdrawAttachmentHandler
{
    public function __construct(
        private AttachmentRepository $attachmentRepository,
        private AttachmentEntityLoader $entityLoader,
        private AttachmentDispatcher $dispatcher,
        private AttachmentDeleter $deleter,
    ) {
    }

    public function __invoke(WithDrawAttachmentCommand $command): AbstractAttachment
    {
        $attachment = $this->entityLoader->loadAndValidateAttachment(
            $command->dossierId,
            $command->attachmentId,
            DossierStatusTransition::UPDATE_ATTACHMENT,
        );

        if (! $attachment->canWithdraw()) {
            throw AttachmentWithdrawException::forCannotWithdraw();
        }

        $this->deleter->delete($attachment);

        $attachment->withdraw($command->reason, $command->explanation);

        $this->attachmentRepository->save($attachment, true);

        $this->dispatcher->dispatchAttachmentWithdrawnEvent($attachment);

        return $attachment;
    }
}
