<?php

declare(strict_types=1);

namespace App\Api\Admin\CovenantDocument;

use App\Api\Admin\Document\DocumentDto;
use App\Api\Admin\Document\DocumentProvider;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantDocumentRepository;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\Publication\MainDocument\MainDocumentRepositoryInterface;

final readonly class CovenantDocumentProvider extends DocumentProvider
{
    public function __construct(
        private CovenantDocumentRepository $documentRepository,
    ) {
    }

    protected function fromEntityToDto(AbstractMainDocument $entity): DocumentDto
    {
        return CovenantDocumentDto::fromEntity($entity);
    }

    protected function getAttachmentRepository(): MainDocumentRepositoryInterface
    {
        return $this->documentRepository;
    }
}
