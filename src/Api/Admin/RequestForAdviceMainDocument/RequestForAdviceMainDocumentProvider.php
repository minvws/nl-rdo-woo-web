<?php

declare(strict_types=1);

namespace App\Api\Admin\RequestForAdviceMainDocument;

use App\Api\Admin\AbstractMainDocument\AbstractMainDocumentDto;
use App\Api\Admin\AbstractMainDocument\AbstractMainDocumentProvider;
use App\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceMainDocument;
use App\Domain\Publication\MainDocument\AbstractMainDocument;

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
