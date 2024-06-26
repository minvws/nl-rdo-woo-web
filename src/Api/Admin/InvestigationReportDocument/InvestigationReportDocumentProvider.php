<?php

declare(strict_types=1);

namespace App\Api\Admin\InvestigationReportDocument;

use App\Api\Admin\Document\DocumentDto;
use App\Api\Admin\Document\DocumentProvider;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportDocumentRepository;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\Publication\MainDocument\MainDocumentRepositoryInterface;

final readonly class InvestigationReportDocumentProvider extends DocumentProvider
{
    public function __construct(
        private InvestigationReportDocumentRepository $documentRepository,
    ) {
    }

    protected function fromEntityToDto(AbstractMainDocument $entity): DocumentDto
    {
        return InvestigationReportDocumentDto::fromEntity($entity);
    }

    protected function getAttachmentRepository(): MainDocumentRepositoryInterface
    {
        return $this->documentRepository;
    }
}
