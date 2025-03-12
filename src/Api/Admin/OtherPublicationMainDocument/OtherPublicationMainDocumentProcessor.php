<?php

declare(strict_types=1);

namespace App\Api\Admin\OtherPublicationMainDocument;

use App\Api\Admin\AbstractMainDocument\AbstractMainDocumentDto;
use App\Api\Admin\AbstractMainDocument\AbstractMainDocumentProcessor;
use App\Domain\Publication\MainDocument\AbstractMainDocument;

final class OtherPublicationMainDocumentProcessor extends AbstractMainDocumentProcessor
{
    protected function fromEntityToDto(AbstractMainDocument $entity): AbstractMainDocumentDto
    {
        return OtherPublicationMainDocumentDto::fromEntity($entity);
    }
}
