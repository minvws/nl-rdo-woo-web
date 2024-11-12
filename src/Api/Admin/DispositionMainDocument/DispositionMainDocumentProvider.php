<?php

declare(strict_types=1);

namespace App\Api\Admin\DispositionMainDocument;

use App\Api\Admin\AbstractMainDocument\AbstractMainDocumentDto;
use App\Api\Admin\AbstractMainDocument\AbstractMainDocumentProvider;
use App\Domain\Publication\Dossier\Type\Disposition\DispositionMainDocumentRepository;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\Publication\MainDocument\MainDocumentRepositoryInterface;

final readonly class DispositionMainDocumentProvider extends AbstractMainDocumentProvider
{
    public function __construct(
        private DispositionMainDocumentRepository $documentRepository,
    ) {
    }

    protected function fromEntityToDto(AbstractMainDocument $entity): AbstractMainDocumentDto
    {
        return DispositionMainDocumentDto::fromEntity($entity);
    }

    protected function getAttachmentRepository(): MainDocumentRepositoryInterface
    {
        return $this->documentRepository;
    }
}
