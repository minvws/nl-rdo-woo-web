<?php

declare(strict_types=1);

namespace Admin\Api\Admin\CovenantAttachment;

use Admin\Api\Admin\Attachment\AbstractAttachmentDto;
use Admin\Api\Admin\Attachment\AbstractAttachmentProvider;
use Admin\Api\Admin\Attachment\AttachmentDtoFactory;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Repository\AttachmentRepositoryInterface;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantAttachmentRepository;

final readonly class CovenantAttachmentProvider extends AbstractAttachmentProvider
{
    public function __construct(
        private CovenantAttachmentRepository $attachmentRepository,
        private AttachmentDtoFactory $dtoFactory,
    ) {
    }

    protected function fromEntityToDto(AbstractAttachment $entity): AbstractAttachmentDto
    {
        return $this->dtoFactory->make(CovenantAttachmentDto::class, $entity);
    }

    protected function getAttachmentRepository(): AttachmentRepositoryInterface
    {
        return $this->attachmentRepository;
    }
}
