<?php

declare(strict_types=1);

namespace Admin\Api\Admin\ComplaintJudgementMainDocument;

use Admin\Api\Admin\AbstractMainDocument\AbstractMainDocumentDto;
use Admin\Api\Admin\AbstractMainDocument\AbstractMainDocumentProvider;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementMainDocument;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;

final readonly class ComplaintJudgementMainDocumentProvider extends AbstractMainDocumentProvider
{
    protected function fromEntityToDto(AbstractMainDocument $entity): AbstractMainDocumentDto
    {
        return ComplaintJudgementMainDocumentDto::fromEntity($entity);
    }

    protected function getEntityClass(): string
    {
        return ComplaintJudgementMainDocument::class;
    }
}
