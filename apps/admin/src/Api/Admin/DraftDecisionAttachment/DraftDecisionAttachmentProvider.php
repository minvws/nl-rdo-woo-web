<?php

declare(strict_types=1);

namespace Admin\Api\Admin\DraftDecisionAttachment;

use Admin\Api\Admin\Attachment\AbstractAttachmentDto;
use Admin\Api\Admin\Attachment\AbstractAttachmentProvider;
use Admin\Api\Admin\Attachment\AttachmentDtoFactory;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Repository\AttachmentRepositoryInterface;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionAttachmentRepository;

final readonly class DraftDecisionAttachmentProvider extends AbstractAttachmentProvider
{
    public function __construct(
        private DraftDecisionAttachmentRepository $attachmentRepository,
        private AttachmentDtoFactory $dtoFactory,
    ) {
    }

    protected function fromEntityToDto(AbstractAttachment $entity): AbstractAttachmentDto
    {
        return $this->dtoFactory->make(DraftDecisionAttachmentDto::class, $entity);
    }

    protected function getAttachmentRepository(): AttachmentRepositoryInterface
    {
        return $this->attachmentRepository;
    }
}
