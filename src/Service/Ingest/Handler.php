<?php

declare(strict_types=1);

namespace App\Service\Ingest;

use App\Entity\Document;
use App\Entity\FileInfo;

interface Handler
{
    /**
     * Handles the actual ingest of the document. Passes $options to provide additional information to the handler.
     */
    public function handle(Document $document, Options $options): void;

    /**
     * Returns true when this handler can handle the given FileInfo.
     */
    public function canHandle(FileInfo $fileInfo): bool;
}
