<?php

declare(strict_types=1);

namespace Shared\Api\Admin\AnnualReportAttachment;

use Shared\Api\Admin\Attachment\AbstractAttachmentDto;
use Shared\Api\Admin\Attachment\AbstractAttachmentProvider;
use Shared\Api\Admin\Attachment\AttachmentDtoFactory;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Repository\AttachmentRepositoryInterface;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportAttachmentRepository;

final readonly class AnnualReportAttachmentProvider extends AbstractAttachmentProvider
{
    public function __construct(
        private AnnualReportAttachmentRepository $attachmentRepository,
        private AttachmentDtoFactory $dtoFactory,
    ) {
    }

    protected function fromEntityToDto(AbstractAttachment $entity): AbstractAttachmentDto
    {
        return $this->dtoFactory->make(AnnualReportAttachmentDto::class, $entity);
    }

    protected function getAttachmentRepository(): AttachmentRepositoryInterface
    {
        return $this->attachmentRepository;
    }
}
