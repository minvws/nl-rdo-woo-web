<?php

declare(strict_types=1);

namespace Admin\Api\Admin\CovenantMainDocument;

use Admin\Api\Admin\AbstractMainDocument\AbstractMainDocumentDto;
use Admin\Api\Admin\AbstractMainDocument\AbstractMainDocumentProcessor;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;

final class CovenantMainDocumentProcessor extends AbstractMainDocumentProcessor
{
    protected function fromEntityToDto(AbstractMainDocument $entity): AbstractMainDocumentDto
    {
        return CovenantMainDocumentDto::fromEntity($entity);
    }
}
