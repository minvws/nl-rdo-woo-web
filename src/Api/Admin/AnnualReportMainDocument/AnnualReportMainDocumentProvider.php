<?php

declare(strict_types=1);

namespace Shared\Api\Admin\AnnualReportMainDocument;

use Shared\Api\Admin\AbstractMainDocument\AbstractMainDocumentDto;
use Shared\Api\Admin\AbstractMainDocument\AbstractMainDocumentProvider;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocument;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;

final readonly class AnnualReportMainDocumentProvider extends AbstractMainDocumentProvider
{
    protected function fromEntityToDto(AbstractMainDocument $entity): AbstractMainDocumentDto
    {
        return AnnualReportMainDocumentDto::fromEntity($entity);
    }

    protected function getEntityClass(): string
    {
        return AnnualReportMainDocument::class;
    }
}
