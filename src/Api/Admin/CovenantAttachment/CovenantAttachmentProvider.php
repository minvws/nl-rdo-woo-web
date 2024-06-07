<?php

declare(strict_types=1);

namespace App\Api\Admin\CovenantAttachment;

use App\Api\Admin\Attachment\AttachmentDto;
use App\Api\Admin\Attachment\AttachmentProvider;
use App\Domain\Publication\Attachment\AbstractAttachment;
use App\Domain\Publication\Attachment\AttachmentRepositoryInterface;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachmentRepository;

final readonly class CovenantAttachmentProvider extends AttachmentProvider
{
    public function __construct(
        private CovenantAttachmentRepository $attachmentRepository,
    ) {
    }

    protected function fromEntityToDto(AbstractAttachment $entity): AttachmentDto
    {
        return CovenantAttachmentDto::fromEntity($entity);
    }

    protected function getAttachmentRepository(): AttachmentRepositoryInterface
    {
        return $this->attachmentRepository;
    }
}
