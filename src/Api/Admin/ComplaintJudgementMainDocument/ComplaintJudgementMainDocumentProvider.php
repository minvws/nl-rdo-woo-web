<?php

declare(strict_types=1);

namespace App\Api\Admin\ComplaintJudgementMainDocument;

use App\Api\Admin\AbstractMainDocument\AbstractMainDocumentDto;
use App\Api\Admin\AbstractMainDocument\AbstractMainDocumentProvider;
use App\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementMainDocument;
use App\Domain\Publication\MainDocument\AbstractMainDocument;

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
