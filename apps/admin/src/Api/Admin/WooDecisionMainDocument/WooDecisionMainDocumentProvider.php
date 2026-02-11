<?php

declare(strict_types=1);

namespace Admin\Api\Admin\WooDecisionMainDocument;

use Admin\Api\Admin\AbstractMainDocument\AbstractMainDocumentDto;
use Admin\Api\Admin\AbstractMainDocument\AbstractMainDocumentProvider;
use Shared\Domain\Publication\Dossier\Type\WooDecision\MainDocument\WooDecisionMainDocument;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;

final readonly class WooDecisionMainDocumentProvider extends AbstractMainDocumentProvider
{
    protected function fromEntityToDto(AbstractMainDocument $entity): AbstractMainDocumentDto
    {
        return WooDecisionMainDocumentDto::fromEntity($entity);
    }

    protected function getEntityClass(): string
    {
        return WooDecisionMainDocument::class;
    }
}
