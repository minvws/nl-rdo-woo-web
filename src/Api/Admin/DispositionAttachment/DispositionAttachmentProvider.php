<?php

declare(strict_types=1);

namespace App\Api\Admin\DispositionAttachment;

use App\Api\Admin\Attachment\AbstractAttachmentDto;
use App\Api\Admin\Attachment\AbstractAttachmentProvider;
use App\Api\Admin\Attachment\AttachmentDtoFactory;
use App\Domain\Publication\Attachment\Entity\AbstractAttachment;
use App\Domain\Publication\Attachment\Repository\AttachmentRepositoryInterface;
use App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachmentRepository;

final readonly class DispositionAttachmentProvider extends AbstractAttachmentProvider
{
    public function __construct(
        private DispositionAttachmentRepository $attachmentRepository,
        private AttachmentDtoFactory $dtoFactory,
    ) {
    }

    protected function fromEntityToDto(AbstractAttachment $entity): AbstractAttachmentDto
    {
        return $this->dtoFactory->make(DispositionAttachmentDto::class, $entity);
    }

    protected function getAttachmentRepository(): AttachmentRepositoryInterface
    {
        return $this->attachmentRepository;
    }
}
