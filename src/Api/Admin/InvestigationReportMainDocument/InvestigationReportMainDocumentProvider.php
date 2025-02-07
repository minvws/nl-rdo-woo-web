<?php

declare(strict_types=1);

namespace App\Api\Admin\InvestigationReportMainDocument;

use App\Api\Admin\AbstractMainDocument\AbstractMainDocumentDto;
use App\Api\Admin\AbstractMainDocument\AbstractMainDocumentProvider;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportMainDocumentRepository;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\Publication\MainDocument\MainDocumentRepositoryInterface;

final readonly class InvestigationReportMainDocumentProvider extends AbstractMainDocumentProvider
{
    public function __construct(
        private InvestigationReportMainDocumentRepository $documentRepository,
    ) {
    }

    protected function fromEntityToDto(AbstractMainDocument $entity): AbstractMainDocumentDto
    {
        return InvestigationReportMainDocumentDto::fromEntity($entity);
    }

    protected function getAttachmentRepository(): MainDocumentRepositoryInterface
    {
        return $this->documentRepository;
    }
}
