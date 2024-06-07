<?php

declare(strict_types=1);

namespace App\Api\Admin\DispositionAttachment;

use App\Api\Admin\Attachment\AttachmentDto;
use App\Api\Admin\Attachment\AttachmentProvider;
use App\Domain\Publication\Attachment\AbstractAttachment;
use App\Domain\Publication\Attachment\AttachmentRepositoryInterface;
use App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachmentRepository;

final readonly class DispositionAttachmentProvider extends AttachmentProvider
{
    public function __construct(
        private DispositionAttachmentRepository $attachmentRepository,
    ) {
    }

    protected function fromEntityToDto(AbstractAttachment $entity): AttachmentDto
    {
        return DispositionAttachmentDto::fromEntity($entity);
    }

    protected function getAttachmentRepository(): AttachmentRepositoryInterface
    {
        return $this->attachmentRepository;
    }
}
