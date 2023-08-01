<?php

declare(strict_types=1);

namespace App\Service\Ingest;

use App\Entity\Document;

interface Handler
{
    /**
     * Handles the actual ingest of the document. Passes $options to provide additional information to the handler.
     */
    public function handle(Document $document, Options $options): void;

    /**
     * Returns true when this handler can handle the given mimetype.
     */
    public function canHandle(string $mimeType): bool;
}
