<?php

declare(strict_types=1);

namespace App\Api\Admin\OtherPublicationMainDocument;

use App\Api\Admin\AbstractMainDocument\AbstractMainDocumentDto;
use App\Api\Admin\AbstractMainDocument\AbstractMainDocumentProvider;
use App\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationMainDocument;
use App\Domain\Publication\MainDocument\AbstractMainDocument;

final readonly class OtherPublicationMainDocumentProvider extends AbstractMainDocumentProvider
{
    protected function fromEntityToDto(AbstractMainDocument $entity): AbstractMainDocumentDto
    {
        return OtherPublicationMainDocumentDto::fromEntity($entity);
    }

    protected function getEntityClass(): string
    {
        return OtherPublicationMainDocument::class;
    }
}
