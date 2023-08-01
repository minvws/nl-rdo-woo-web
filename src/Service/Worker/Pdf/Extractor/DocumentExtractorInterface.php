<?php

declare(strict_types=1);

namespace App\Service\Worker\Pdf\Extractor;

use App\Entity\Document;

interface DocumentExtractorInterface
{
    public function extract(Document $document, bool $forceRefresh): void;
}
