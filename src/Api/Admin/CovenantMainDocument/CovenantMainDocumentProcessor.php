<?php

declare(strict_types=1);

namespace App\Api\Admin\CovenantMainDocument;

use App\Api\Admin\AbstractMainDocument\AbstractMainDocumentDto;
use App\Api\Admin\AbstractMainDocument\AbstractMainDocumentProcessor;
use App\Domain\Publication\MainDocument\AbstractMainDocument;

final class CovenantMainDocumentProcessor extends AbstractMainDocumentProcessor
{
    protected function fromEntityToDto(AbstractMainDocument $entity): AbstractMainDocumentDto
    {
        return CovenantMainDocumentDto::fromEntity($entity);
    }
}
