<?php

declare(strict_types=1);

namespace Shared\Api\Admin\AdviceMainDocument;

use Shared\Api\Admin\AbstractMainDocument\AbstractMainDocumentDto;
use Shared\Api\Admin\AbstractMainDocument\AbstractMainDocumentProvider;
use Shared\Domain\Publication\Dossier\Type\Advice\AdviceMainDocument;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;

final readonly class AdviceMainDocumentProvider extends AbstractMainDocumentProvider
{
    protected function fromEntityToDto(AbstractMainDocument $entity): AbstractMainDocumentDto
    {
        return AdviceMainDocumentDto::fromEntity($entity);
    }

    protected function getEntityClass(): string
    {
        return AdviceMainDocument::class;
    }
}
