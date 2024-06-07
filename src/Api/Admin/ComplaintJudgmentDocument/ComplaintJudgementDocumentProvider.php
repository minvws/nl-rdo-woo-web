<?php

declare(strict_types=1);

namespace App\Api\Admin\ComplaintJudgmentDocument;

use App\Api\Admin\Document\DocumentDto;
use App\Api\Admin\Document\DocumentProvider;
use App\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementDocumentRepository;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\Publication\MainDocument\MainDocumentRepositoryInterface;

final readonly class ComplaintJudgementDocumentProvider extends DocumentProvider
{
    public function __construct(
        private ComplaintJudgementDocumentRepository $documentRepository,
    ) {
    }

    protected function fromEntityToDto(AbstractMainDocument $entity): DocumentDto
    {
        return ComplaintJudgementDocumentDto::fromEntity($entity);
    }

    protected function getAttachmentRepository(): MainDocumentRepositoryInterface
    {
        return $this->documentRepository;
    }
}
