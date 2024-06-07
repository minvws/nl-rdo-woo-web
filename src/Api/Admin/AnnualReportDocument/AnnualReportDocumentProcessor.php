<?php

declare(strict_types=1);

namespace App\Api\Admin\AnnualReportDocument;

use App\Api\Admin\Attachment\AttachmentCreateDto;
use App\Api\Admin\Attachment\AttachmentUpdateDto;
use App\Api\Admin\Document\DocumentDto;
use App\Api\Admin\Document\DocumentProcessor;
use App\Domain\Publication\MainDocument\AbstractMainDocument;

final class AnnualReportDocumentProcessor extends DocumentProcessor
{
    /**
     * @return class-string<AttachmentCreateDto>
     */
    protected function getCreateDtoClass(): string
    {
        return AnnualReportDocumentCreateDto::class;
    }

    /**
     * @return class-string<AttachmentUpdateDto>
     */
    protected function getUpdateDtoClass(): string
    {
        return AnnualReportDocumentUpdateDto::class;
    }

    protected function fromEntityToDto(AbstractMainDocument $entity): DocumentDto
    {
        return AnnualReportDocumentDto::fromEntity($entity);
    }
}
