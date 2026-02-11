<?php

declare(strict_types=1);

namespace Admin\Api\Admin\OtherPublicationMainDocument;

use Admin\Api\Admin\AbstractMainDocument\AbstractMainDocumentDto;
use Admin\Api\Admin\AbstractMainDocument\AbstractMainDocumentProvider;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationMainDocument;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;

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
