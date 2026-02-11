<?php

declare(strict_types=1);

namespace Admin\Api\Admin\RequestForAdviceMainDocument;

use Admin\Api\Admin\AbstractMainDocument\AbstractMainDocumentDto;
use Admin\Api\Admin\AbstractMainDocument\AbstractMainDocumentProvider;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceMainDocument;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;

final readonly class RequestForAdviceMainDocumentProvider extends AbstractMainDocumentProvider
{
    protected function fromEntityToDto(AbstractMainDocument $entity): AbstractMainDocumentDto
    {
        return RequestForAdviceMainDocumentDto::fromEntity($entity);
    }

    protected function getEntityClass(): string
    {
        return RequestForAdviceMainDocument::class;
    }
}
