<?php

declare(strict_types=1);

namespace App\Api\Admin\AdviceAttachment;

use App\Api\Admin\Attachment\AbstractAttachmentDto;
use App\Api\Admin\Attachment\AbstractAttachmentProvider;
use App\Api\Admin\Attachment\AttachmentDtoFactory;
use App\Domain\Publication\Attachment\Entity\AbstractAttachment;
use App\Domain\Publication\Attachment\Repository\AttachmentRepositoryInterface;
use App\Domain\Publication\Dossier\Type\Advice\AdviceAttachmentRepository;

final readonly class AdviceAttachmentProvider extends AbstractAttachmentProvider
{
    public function __construct(
        private AdviceAttachmentRepository $attachmentRepository,
        private AttachmentDtoFactory $dtoFactory,
    ) {
    }

    protected function fromEntityToDto(AbstractAttachment $entity): AbstractAttachmentDto
    {
        return $this->dtoFactory->make(AdviceAttachmentDto::class, $entity);
    }

    protected function getAttachmentRepository(): AttachmentRepositoryInterface
    {
        return $this->attachmentRepository;
    }
}
