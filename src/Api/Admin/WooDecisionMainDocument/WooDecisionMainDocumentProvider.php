<?php

declare(strict_types=1);

namespace App\Api\Admin\WooDecisionMainDocument;

use App\Api\Admin\AbstractMainDocument\AbstractMainDocumentDto;
use App\Api\Admin\AbstractMainDocument\AbstractMainDocumentProvider;
use App\Domain\Publication\Dossier\Type\WooDecision\MainDocument\WooDecisionMainDocument;
use App\Domain\Publication\MainDocument\AbstractMainDocument;

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
