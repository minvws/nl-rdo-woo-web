<?php

declare(strict_types=1);

namespace App\Api\Admin\CovenantMainDocument;

use App\Api\Admin\AbstractMainDocument\AbstractMainDocumentDto;
use App\Api\Admin\AbstractMainDocument\AbstractMainDocumentProvider;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocument;
use App\Domain\Publication\MainDocument\AbstractMainDocument;

final readonly class CovenantMainDocumentProvider extends AbstractMainDocumentProvider
{
    protected function fromEntityToDto(AbstractMainDocument $entity): AbstractMainDocumentDto
    {
        return CovenantMainDocumentDto::fromEntity($entity);
    }

    protected function getEntityClass(): string
    {
        return CovenantMainDocument::class;
    }
}
