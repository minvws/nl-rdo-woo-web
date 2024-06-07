<?php

declare(strict_types=1);

namespace App\Api\Admin\CovenantDocument;

use App\Api\Admin\Attachment\AttachmentCreateDto;
use App\Api\Admin\Attachment\AttachmentUpdateDto;
use App\Api\Admin\Document\DocumentDto;
use App\Api\Admin\Document\DocumentProcessor;
use App\Domain\Publication\MainDocument\AbstractMainDocument;

final class CovenantDocumentProcessor extends DocumentProcessor
{
    /**
     * @return class-string<AttachmentCreateDto>
     */
    protected function getCreateDtoClass(): string
    {
        return CovenantDocumentCreateDto::class;
    }

    /**
     * @return class-string<AttachmentUpdateDto>
     */
    protected function getUpdateDtoClass(): string
    {
        return CovenantDocumentUpdateDto::class;
    }

    protected function fromEntityToDto(AbstractMainDocument $entity): DocumentDto
    {
        return CovenantDocumentDto::fromEntity($entity);
    }
}
