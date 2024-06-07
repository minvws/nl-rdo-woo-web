<?php

declare(strict_types=1);

namespace App\Api\Admin\AnnualReportDocument;

use App\Api\Admin\Document\DocumentDto;
use App\Api\Admin\Document\DocumentProvider;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportDocumentRepository;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\Publication\MainDocument\MainDocumentRepositoryInterface;

final readonly class AnnualReportDocumentProvider extends DocumentProvider
{
    public function __construct(
        private AnnualReportDocumentRepository $documentRepository,
    ) {
    }

    protected function fromEntityToDto(AbstractMainDocument $entity): DocumentDto
    {
        return AnnualReportDocumentDto::fromEntity($entity);
    }

    protected function getAttachmentRepository(): MainDocumentRepositoryInterface
    {
        return $this->documentRepository;
    }
}
