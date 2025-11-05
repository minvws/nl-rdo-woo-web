<?php

declare(strict_types=1);

namespace App\Api\Admin\DispositionMainDocument;

use App\Api\Admin\AbstractMainDocument\AbstractMainDocumentDto;
use App\Api\Admin\AbstractMainDocument\AbstractMainDocumentProvider;
use App\Domain\Publication\Dossier\Type\Disposition\DispositionMainDocument;
use App\Domain\Publication\MainDocument\AbstractMainDocument;

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
