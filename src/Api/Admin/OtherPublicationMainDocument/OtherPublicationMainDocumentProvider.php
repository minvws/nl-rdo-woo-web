<?php

declare(strict_types=1);

namespace App\Api\Admin\OtherPublicationMainDocument;

use App\Api\Admin\AbstractMainDocument\AbstractMainDocumentDto;
use App\Api\Admin\AbstractMainDocument\AbstractMainDocumentProvider;
use App\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationMainDocumentRepository;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\Publication\MainDocument\MainDocumentRepositoryInterface;

final readonly class OtherPublicationMainDocumentProvider extends AbstractMainDocumentProvider
{
    public function __construct(
        private OtherPublicationMainDocumentRepository $documentRepository,
    ) {
    }

    protected function fromEntityToDto(AbstractMainDocument $entity): AbstractMainDocumentDto
    {
        return OtherPublicationMainDocumentDto::fromEntity($entity);
    }

    protected function getAttachmentRepository(): MainDocumentRepositoryInterface
    {
        return $this->documentRepository;
    }
}
