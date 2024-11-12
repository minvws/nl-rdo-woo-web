<?php

declare(strict_types=1);

namespace App\Api\Admin\InvestigationReportAttachment;

use App\Api\Admin\Attachment\AbstractAttachmentDto;
use App\Api\Admin\Attachment\AbstractAttachmentProvider;
use App\Domain\Publication\Attachment\AbstractAttachment;
use App\Domain\Publication\Attachment\AttachmentRepositoryInterface;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportAttachmentRepository;

final readonly class InvestigationReportAttachmentProvider extends AbstractAttachmentProvider
{
    public function __construct(
        private InvestigationReportAttachmentRepository $attachmentRepository,
    ) {
    }

    protected function fromEntityToDto(AbstractAttachment $entity): AbstractAttachmentDto
    {
        return InvestigationReportAttachmentDto::fromEntity($entity);
    }

    protected function getAttachmentRepository(): AttachmentRepositoryInterface
    {
        return $this->attachmentRepository;
    }
}
