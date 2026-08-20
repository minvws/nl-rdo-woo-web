<?php

declare(strict_types=1);

namespace Admin\Api\Admin\DraftDecisionMainDocument;

use Admin\Api\Admin\AbstractMainDocument\AbstractMainDocumentDto;
use Admin\Api\Admin\AbstractMainDocument\AbstractMainDocumentProvider;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionMainDocument;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;

final readonly class DraftDecisionMainDocumentProvider extends AbstractMainDocumentProvider
{
    protected function fromEntityToDto(AbstractMainDocument $entity): AbstractMainDocumentDto
    {
        return DraftDecisionMainDocumentDto::fromEntity($entity);
    }

    protected function getEntityClass(): string
    {
        return DraftDecisionMainDocument::class;
    }
}
