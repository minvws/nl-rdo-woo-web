<?php

declare(strict_types=1);

namespace App\Service\Worker\Audio\Extractor;

use App\Entity\Document;

interface AudioExtractorInterface
{
    public function extract(Document $document, bool $forceRefresh): void;
}
