<?php

declare(strict_types=1);

namespace App\Api\Admin\DispositionDocument;

use App\Api\Admin\Document\DocumentDto;
use App\Api\Admin\Document\DocumentProvider;
use App\Domain\Publication\Dossier\Type\Disposition\DispositionDocumentRepository;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\Publication\MainDocument\MainDocumentRepositoryInterface;

final readonly class DispositionDocumentProvider extends DocumentProvider
{
    public function __construct(
        private DispositionDocumentRepository $documentRepository,
    ) {
    }

    protected function fromEntityToDto(AbstractMainDocument $entity): DocumentDto
    {
        return DispositionDocumentDto::fromEntity($entity);
    }

    protected function getAttachmentRepository(): MainDocumentRepositoryInterface
    {
        return $this->documentRepository;
    }
}
