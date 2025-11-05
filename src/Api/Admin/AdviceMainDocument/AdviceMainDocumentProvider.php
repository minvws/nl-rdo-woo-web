<?php

declare(strict_types=1);

namespace App\Api\Admin\AdviceMainDocument;

use App\Api\Admin\AbstractMainDocument\AbstractMainDocumentDto;
use App\Api\Admin\AbstractMainDocument\AbstractMainDocumentProvider;
use App\Domain\Publication\Dossier\Type\Advice\AdviceMainDocument;
use App\Domain\Publication\MainDocument\AbstractMainDocument;

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
