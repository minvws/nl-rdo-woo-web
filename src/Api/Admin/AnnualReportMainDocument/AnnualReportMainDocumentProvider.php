<?php

declare(strict_types=1);

namespace App\Api\Admin\AnnualReportMainDocument;

use App\Api\Admin\AbstractMainDocument\AbstractMainDocumentDto;
use App\Api\Admin\AbstractMainDocument\AbstractMainDocumentProvider;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocument;
use App\Domain\Publication\MainDocument\AbstractMainDocument;

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
