<?php

declare(strict_types=1);

namespace Shared\Api\Admin\CovenantMainDocument;

use Shared\Api\Admin\AbstractMainDocument\AbstractMainDocumentDto;
use Shared\Api\Admin\AbstractMainDocument\AbstractMainDocumentProvider;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocument;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;

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
