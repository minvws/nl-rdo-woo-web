<?php

declare(strict_types=1);

namespace App\Api\Admin\ComplaintJudgmentDocument;

use App\Api\Admin\Attachment\AttachmentCreateDto;
use App\Api\Admin\Attachment\AttachmentUpdateDto;
use App\Api\Admin\Document\DocumentDto;
use App\Api\Admin\Document\DocumentProcessor;
use App\Domain\Publication\MainDocument\AbstractMainDocument;

final class ComplaintJudgementDocumentProcessor extends DocumentProcessor
{
    /**
     * @return class-string<AttachmentCreateDto>
     */
    protected function getCreateDtoClass(): string
    {
        return ComplaintJudgementDocumentCreateDto::class;
    }

    /**
     * @return class-string<AttachmentUpdateDto>
     */
    protected function getUpdateDtoClass(): string
    {
        return ComplaintJudgementDocumentUpdateDto::class;
    }

    protected function fromEntityToDto(AbstractMainDocument $entity): DocumentDto
    {
        return ComplaintJudgementDocumentDto::fromEntity($entity);
    }
}
