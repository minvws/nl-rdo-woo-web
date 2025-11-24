<?php

declare(strict_types=1);

namespace Shared\Api\Admin\AdviceAttachment;

use Shared\Api\Admin\Attachment\AbstractAttachmentDto;
use Shared\Api\Admin\Attachment\AbstractAttachmentProvider;
use Shared\Api\Admin\Attachment\AttachmentDtoFactory;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Repository\AttachmentRepositoryInterface;
use Shared\Domain\Publication\Dossier\Type\Advice\AdviceAttachmentRepository;

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
