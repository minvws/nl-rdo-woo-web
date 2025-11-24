<?php

declare(strict_types=1);

namespace Shared\Api\Admin\DispositionMainDocument;

use Shared\Api\Admin\AbstractMainDocument\AbstractMainDocumentDto;
use Shared\Api\Admin\AbstractMainDocument\AbstractMainDocumentProvider;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionMainDocument;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;

final readonly class DispositionMainDocumentProvider extends AbstractMainDocumentProvider
{
    protected function fromEntityToDto(AbstractMainDocument $entity): AbstractMainDocumentDto
    {
        return DispositionMainDocumentDto::fromEntity($entity);
    }

    protected function getEntityClass(): string
    {
        return DispositionMainDocument::class;
    }
}
