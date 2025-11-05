<?php

declare(strict_types=1);

namespace App\Api\Admin\InvestigationReportMainDocument;

use App\Api\Admin\AbstractMainDocument\AbstractMainDocumentDto;
use App\Api\Admin\AbstractMainDocument\AbstractMainDocumentProvider;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportMainDocument;
use App\Domain\Publication\MainDocument\AbstractMainDocument;

final readonly class InvestigationReportMainDocumentProvider extends AbstractMainDocumentProvider
{
    protected function fromEntityToDto(AbstractMainDocument $entity): AbstractMainDocumentDto
    {
        return InvestigationReportMainDocumentDto::fromEntity($entity);
    }

    protected function getEntityClass(): string
    {
        return InvestigationReportMainDocument::class;
    }
}
