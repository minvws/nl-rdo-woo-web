<?php

declare(strict_types=1);

namespace App\Service\Worker\Pdf\Extractor;

use App\Entity\Document;

interface PageExtractorInterface
{
    public function extract(Document $document, int $pageNr, bool $forceRefresh): void;
}
