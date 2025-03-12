<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment\Handler;

use App\Domain\Publication\Attachment\AttachmentDeleter;
use App\Domain\Publication\Attachment\AttachmentDispatcher;
use App\Domain\Publication\Attachment\Command\WithDrawAttachmentCommand;
use App\Domain\Publication\Attachment\Entity\AbstractAttachment;
use App\Domain\Publication\Attachment\Exception\AttachmentWithdrawException;
use App\Domain\Publication\Attachment\Repository\AttachmentRepository;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
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
