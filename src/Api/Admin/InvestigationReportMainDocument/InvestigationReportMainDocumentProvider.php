<?php

declare(strict_types=1);

namespace Shared\Api\Admin\InvestigationReportMainDocument;

use Shared\Api\Admin\AbstractMainDocument\AbstractMainDocumentDto;
use Shared\Api\Admin\AbstractMainDocument\AbstractMainDocumentProvider;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportMainDocument;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;

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
