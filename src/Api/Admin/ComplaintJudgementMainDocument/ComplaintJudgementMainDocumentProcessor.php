<?php

declare(strict_types=1);

namespace Shared\Api\Admin\ComplaintJudgementMainDocument;

use Shared\Api\Admin\AbstractMainDocument\AbstractMainDocumentDto;
use Shared\Api\Admin\AbstractMainDocument\AbstractMainDocumentProcessor;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;

final class ComplaintJudgementMainDocumentProcessor extends AbstractMainDocumentProcessor
{
    protected function fromEntityToDto(AbstractMainDocument $entity): AbstractMainDocumentDto
    {
        return ComplaintJudgementMainDocumentDto::fromEntity($entity);
    }
}
