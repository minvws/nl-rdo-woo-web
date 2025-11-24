<?php

declare(strict_types=1);

namespace Shared\Api\Admin\RequestForAdviceMainDocument;

use Shared\Api\Admin\AbstractMainDocument\AbstractMainDocumentDto;
use Shared\Api\Admin\AbstractMainDocument\AbstractMainDocumentProvider;
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
