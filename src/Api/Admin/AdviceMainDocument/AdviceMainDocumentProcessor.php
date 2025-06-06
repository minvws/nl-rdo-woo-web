<?php

declare(strict_types=1);

namespace App\Api\Admin\AdviceMainDocument;

use App\Api\Admin\AbstractMainDocument\AbstractMainDocumentDto;
use App\Api\Admin\AbstractMainDocument\AbstractMainDocumentProcessor;
use App\Domain\Publication\MainDocument\AbstractMainDocument;

final class AdviceMainDocumentProcessor extends AbstractMainDocumentProcessor
{
    protected function fromEntityToDto(AbstractMainDocument $entity): AbstractMainDocumentDto
    {
        return AdviceMainDocumentDto::fromEntity($entity);
    }
}
