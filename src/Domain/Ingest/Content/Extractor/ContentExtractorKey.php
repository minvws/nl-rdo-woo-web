<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Content\Extractor;

enum ContentExtractorKey: string
{
    case TIKA = 'tika';
    case TESSERACT = 'tesseract';
}
