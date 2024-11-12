<?php

declare(strict_types=1);

namespace App\Api\Admin\DecisionAttachment;

use App\Api\Admin\Attachment\AbstractAttachmentDto;
use App\Api\Admin\Attachment\AbstractAttachmentProvider;
use App\Domain\Publication\Attachment\AbstractAttachment;
use App\Domain\Publication\Attachment\AttachmentRepositoryInterface;
use App\Repository\DecisionAttachmentRepository;

final readonly class DecisionAttachmentProvider extends AbstractAttachmentProvider
{
    public function __construct(
        private DecisionAttachmentRepository $attachmentRepository,
    ) {
    }

    protected function fromEntityToDto(AbstractAttachment $entity): AbstractAttachmentDto
    {
        return DecisionAttachmentDto::fromEntity($entity);
    }

    protected function getAttachmentRepository(): AttachmentRepositoryInterface
    {
        return $this->attachmentRepository;
    }
}
