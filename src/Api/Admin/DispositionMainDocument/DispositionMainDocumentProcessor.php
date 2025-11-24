<?php

declare(strict_types=1);

namespace Shared\Api\Admin\DispositionMainDocument;

use Shared\Api\Admin\AbstractMainDocument\AbstractMainDocumentDto;
use Shared\Api\Admin\AbstractMainDocument\AbstractMainDocumentProcessor;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;

final class DispositionMainDocumentProcessor extends AbstractMainDocumentProcessor
{
    protected function fromEntityToDto(AbstractMainDocument $entity): AbstractMainDocumentDto
    {
        return DispositionMainDocumentDto::fromEntity($entity);
    }
}
